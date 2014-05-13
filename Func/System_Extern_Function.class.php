<?php

namespace _extern_object\Func;

use _php_base\Helper\SHC;

class System_Extern_Function //bietet Primitive functionen für Externe Objekte. Update by Objekt Add objekt
{

    public function GetJoinByDbName($db_name) //erhalte korrekten join je nach Objekt
    {
        $join = "";
        $additional_join = false;

        switch ($db_name)
        {
            case "user_order_element":
                $join .=  "LEFT JOIN user_product_data ON user_order_element.user_product_data_id = user_product_data.user_product_data_id";
                $additional_join = true;
                break;
            case "user_order_addition":
                $join .=  " LEFT JOIN user_addition_data ON user_order_addition.user_addition_data_id = user_addition_data.user_addition_data_id";
                $additional_join = true;
                break;
        }

        if($additional_join)
        {
            $join .= " LEFT JOIN order_element ON {$db_name}.order_element_id = order_element.order_element_id LEFT JOIN rech_element ON order_element.rech_element_id = rech_element.rech_element_id LEFT JOIN system_work ON order_element.system_work_id = system_work.system_work_id";
        }
        return $join;
    }

    public function GetTableNameByObject($object) //Liefert:[0]=
    {
        $obj_name = SHC::GetObjectName($object);//User_Order
        $db_name = strtolower($obj_name); //user_order

        return $db_name;
    }

    public function GetIdNameByObject($object) //Liefert:[0]=
    {
        $obj_name = SHC::GetObjectName($object);//User_Order
        $db_name = strtolower($obj_name); //user_order

        return $db_name."_id"; //user_order_id;
    }

    public function GetIdByObject($object)
    {
        $table_name = $this->GetTableNameByObject($object);
        $id_name = $table_name."_id";

        return $object->$id_name;
    }

    public function GetTableNameByContainerArray($container_array)
    {


        return $container_array;
    }


/*
    public function GetObjectArray($object) //Erhalte alle offensichtlichen Objekte in einem Array
    {
        $obj_arr = array();

        foreach($object as $key => $var)
        {
            if(is_object($var))
            {
                $obj_arr[] = $var;
                $this->GetObjectArray();
            }
        }

        return $obj_arr;

    }
*/
} 