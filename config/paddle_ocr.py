#!/usr/bin/env python3
"""
paddle_ocr.py — PaddleOCR wrapper for Tribe file transcription.

Usage:
    python3 /usr/local/bin/paddle_ocr.py <image_path>

Outputs JSON to stdout:
    {"text": "...", "language": "hi", "confidence": 0.92}

Strategy:
  1. Run detection-only pass to find text regions.
  2. Run recognition with each candidate language model.
  3. Pick the language whose recognition yields highest mean confidence.
  4. Return concatenated text + winning language code.

PaddleOCR lang codes for Indian languages:
  hi  = Hindi (Devanagari)
  mr  = Marathi (Devanagari)
  ne  = Nepali (Devanagari)
  ta  = Tamil
  te  = Telugu
  kn  = Kannada
  ml  = Malayalam (added as custom Devanagari fallback if unsupported)
  bn  = Bengali / Bangla
  gu  = Gujarati (added via Devanagari family)
  pa  = Punjabi (Gurmukhi)
  ur  = Urdu (Arabic script)
  ar  = Arabic (covers Urdu script detection)
  en  = English

We group by script family to avoid running all 12+ models on every image.
Step 1: quick probe with 'en' and 'hi' to determine if text exists.
Step 2: if Devanagari-family scores high, test hi/mr/ne/sa.
         if other scripts, test the relevant subset.
For simplicity and reliability, we run a curated set and pick the best.
"""

import sys
import json
import os
import warnings
import logging

# Suppress all the noisy PaddleOCR/PaddlePaddle warnings
warnings.filterwarnings("ignore")
logging.disable(logging.WARNING)
os.environ["GLOG_minloglevel"] = "3"
os.environ["FLAGS_minloglevel"] = "3"
os.environ["PADDLEOCR_HOME"] = "/opt/paddleocr_models"

from paddleocr import PaddleOCR


# Indian language codes supported by PaddleOCR PP-OCRv4/v5
# Grouped to minimize unnecessary model loads
LANG_GROUPS = {
    "devanagari": ["hi", "mr", "ne"],
    "south_indian": ["ta", "te", "kn"],
    "eastern": ["bn", "as"],
    "other_indic": ["gu", "pa"],
    "arabic_script": ["ur", "ar"],
    "latin": ["en"],
}

# Flat list of all candidates (order = probe priority)
ALL_LANGS = ["en", "hi", "ta", "te", "kn", "bn", "gu", "pa", "ur", "mr", "ne", "as", "ar", "ml"]

# Cache OCR instances to avoid re-downloading models within a single run
_ocr_cache = {}


def get_ocr(lang: str) -> PaddleOCR:
    if lang not in _ocr_cache:
        try:
            _ocr_cache[lang] = PaddleOCR(
                lang=lang,
                use_angle_cls=True,
                show_log=False,
                use_gpu=False,
            )
        except Exception:
            # If a language model isn't available, return None
            _ocr_cache[lang] = None
    return _ocr_cache[lang]


def run_ocr(image_path: str, lang: str):
    """Run OCR for a single language. Returns (text, mean_confidence)."""
    ocr = get_ocr(lang)
    if ocr is None:
        return "", 0.0

    try:
        result = ocr.ocr(image_path, cls=True)
    except Exception:
        return "", 0.0

    if not result or not result[0]:
        return "", 0.0

    lines = []
    confidences = []
    for line in result[0]:
        if line and len(line) >= 2 and line[1]:
            text = line[1][0]
            conf = float(line[1][1])
            lines.append(text)
            confidences.append(conf)

    if not lines:
        return "", 0.0

    full_text = "\n".join(lines)
    mean_conf = sum(confidences) / len(confidences)
    return full_text, mean_conf


def detect_and_recognize(image_path: str) -> dict:
    """
    Multi-language OCR: probe with English and Hindi first,
    then fan out to other Indian languages if needed.
    Returns the result with highest confidence.
    """
    best_text = ""
    best_lang = "en"
    best_conf = 0.0

    # Phase 1: Quick probe with English and Hindi
    for lang in ["en", "hi"]:
        text, conf = run_ocr(image_path, lang)
        if conf > best_conf and len(text.strip()) > 0:
            best_text = text
            best_lang = lang
            best_conf = conf

    # If English scored very high (>0.90), likely English — skip other probes
    if best_lang == "en" and best_conf > 0.90:
        return {"text": best_text, "language": best_lang, "confidence": round(best_conf, 4)}

    # If no text detected at all, try a few more and give up
    if best_conf < 0.1:
        for lang in ["ta", "bn", "te", "ur"]:
            text, conf = run_ocr(image_path, lang)
            if conf > best_conf and len(text.strip()) > 0:
                best_text = text
                best_lang = lang
                best_conf = conf
        return {"text": best_text, "language": best_lang, "confidence": round(best_conf, 4)}

    # Phase 2: Test remaining Indian languages
    remaining = [l for l in ALL_LANGS if l not in ("en", "hi")]
    for lang in remaining:
        text, conf = run_ocr(image_path, lang)
        if conf > best_conf and len(text.strip()) > 0:
            best_text = text
            best_lang = lang
            best_conf = conf

    return {"text": best_text, "language": best_lang, "confidence": round(best_conf, 4)}


def main():
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Usage: paddle_ocr.py <image_path>"}), file=sys.stderr)
        sys.exit(1)

    image_path = sys.argv[1]
    if not os.path.isfile(image_path):
        print(json.dumps({"error": f"File not found: {image_path}"}), file=sys.stderr)
        sys.exit(1)

    result = detect_and_recognize(image_path)
    print(json.dumps(result, ensure_ascii=False))


if __name__ == "__main__":
    main()
