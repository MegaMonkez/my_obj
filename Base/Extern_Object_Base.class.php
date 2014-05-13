<?php

namespace _extern_object\Base;

use _conn\Connect\Mysqli_Connect;
use _conn\Extern_Base\Extern_Base;
use _php_base\Helper\SHC;

class Extern_Object_Base extends Extern_Base
{

    protected $table_result = array();

    //Debug
    protected $query_array;
    protected $error_array;

    public function GetQueryArray()
    {
        return $this->query_array;
    }

    public function GetErrorArray()
    {
        return $this->error_array;
    }

    protected function IsTable($check_string) //prüft ob $check_string eine tabelle
    {
        if(empty($this->table_result))
        {
            if($this->_extern_database_conn instanceof Mysqli_Connect)
            {
                $this->table_result = $this->_extern_database_conn->Query("show tables")->ReturnArray();
            }
        }


        foreach($this->table_result as $table)
        {
            foreach($table as $key => $val)
            {
                //echo $val;
                if($val==$check_string)
                {
                    //echo $check_string;
                    return true;
                }

            }
        }


        //return isset($this->table_list[$check_string]);
        //return in_array($check_string,$this->table_list);
    }

    protected function GetTableNameByObject($object) //Liefert:[0]=
    {
        $obj_name = SHC::GetObjectName($object);//User_Order
        $db_name = strtolower($obj_name); //user_order

        return $db_name;
    }

    protected function GetIdNameByObject($object) //Liefert:[0]=
    {
        $obj_name = SHC::GetObjectName($object);//User_Order
        $db_name = strtolower($obj_name); //user_order

        return $db_name."_id"; //user_order_id;
    }

    /*
    public function GetColumnStringByObject($object)
    {
        $cols = array(); //column array wird später imploded
        $object_vars = get_object_vars($object); //bekomme alle objekt variablen

        foreach($object_vars as $key => $var) // Durchlaufe update_data_object
        {
            if(is_object($var)==false && is_array($var)==false)
            {
                $cols[] =  $key."="."'{$var}'";
            }
        }

        $colstring = implode(",",$cols);

        return $colstring;
    }
    */

} 