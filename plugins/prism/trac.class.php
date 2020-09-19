<?php
/*
functions start with push_, pull_, get_, do_ or is_
push_ is to save to database
pull_ is to pull from database, returns 1 or 0, saves the output array in $last_data
get_ is to get usable values from functions
do_ is for action that doesn't have a database push or pull
is_ is for a yes/no answer
*/

class Trac
{
    function push_visit ($post) {
        global $sql;
        $updated_on=time();

        $sql->executeSQL("INSERT INTO `trac` (`created_on`, `visit`) VALUES ('$updated_on', '".json_encode($post)."')");
        return $sql->lastInsertID();
    }

    function push_visit_meta ($id, $meta_key, $meta_value='') {
        global $sql;
        if ($id && $meta_key) {
            if (!trim($meta_value)) {
                //to delete a key, leave it empty
                $q=$sql->executeSQL("UPDATE `trac` SET `visit` = JSON_REMOVE(`visit`, '$.".$meta_key."') WHERE `id`='$id'");
            }
            else {
                $q=$sql->executeSQL("UPDATE `trac` SET `visit` = JSON_SET(`visit`, '$.".$meta_key."', '$meta_value') WHERE `id`='$id'");
            }
            return 1;
        }
        else
        return 0;
    }

    function get_visit ($val) {
        global $sql, $session_user;
        $or=array();
        $q=$sql->executeSQL("SELECT * FROM `trac` WHERE `id`='$val'");
        if ($q[0]['id']) {
            $or=json_decode($q[0]['visit'], true);
            $or['id']=$q[0]['id'];
            $or['updated_on']=$q[0]['updated_on'];
            $or['created_on']=$q[0]['created_on'];
            return $or;
        }
        else
        return 0;
    }

    function get_visit_meta ($val, $meta_key) {
        global $sql;

        if ($meta_key=='id' || $meta_key=='updated_on' || $meta_key=='created_on')
        $qry="`".$meta_key."`";
        else
        $qry="`visit`->>'$.".$meta_key."' `".$meta_key."`";

        if (is_numeric($val))
        $q=$sql->executeSQL("SELECT ".$qry." FROM `trac` WHERE `id`='$val'");
        else
        $q=$sql->executeSQL("SELECT ".$qry." FROM `trac` WHERE `visit`->'$.slug'='".$val['slug']."' && `visit`->'$.type'='".$val['type']."'");

        return $q[0][$meta_key];
    }

    /**
     * pass 'limit' to the function to get values for last 24 hours only
     */
    function get_unique_visits ($val = null) {
        global $sql, $session_user;

        if ($val != 'limit') {
            $q = $sql->executeSQL("SELECT count(distinct visit->>'$.HTTP_COOKIE') as visit_count from trac");
        } else {
            $q = $sql->executeSQL("SELECT count(distinct visit->>'$.HTTP_COOKIE') as visit_count
            from trac where created_on >= unix_timestamp(now() - interval 1 day)");
        }

        return $q[0];
    }

    /**
     * pass 'limit' to the function to get values for last 24 hours only
     */
    function get_page_visits ($val = null) {
        global $sql;

        if ($val != 'limit') {
            $q = $sql->executeSQL("SELECT count(visit->>'$.HTTP_COOKIE') as visit_count from trac");
        } else {
            $q = $sql->executeSQL("SELECT count(visit->>'$.HTTP_COOKIE') as visit_count
            from trac where created_on >= unix_timestamp(now() - interval 1 day)");
        }

        return $q[0];
    }
}
?>
