<?php

namespace _extern_object\Method;


use _data_element\Element\Update_Data_Object;
use _extern_object\Base\Extern_Object_Base;
use _extern_object\Func\System_Extern_Function;
use _php_base\Helper\SHC;

class Extern_Object_Update extends Extern_Object_Base
{
    //Nimmt Objekt an als rohe Daten, erzeugt php Objekt, bekommt Objektliste, anhand Liste wird abgearbeitet

    //usage 1: //Setze Objekte die updated werden sollen, wenn diese ein update_data_object enthalten,
    //SetObjectArray($obj_array)
    //Update($obj_array)

    //usage 2: //Übermittle ein Object, updated wenn dieses ein update_data_object enthält
    //DoUpdateByObject($obj)

    //usage 3: //TODO: Übermittle ein Object, updated alle felder auch ohne update_data_object
    //DoUpdateByWholeObject($obj)
    private $system_extern_function;

    private $object_array;
    //private $_extern_database_conn;

    public function __construct()
    {
        $this->system_extern_function = new System_Extern_Function();
    }

    public function SetObjectArray($object_array)
    {

        $this->object_array = $object_array;
    }
/*
    public function SetExternDatabaseConnection($extern_database_conn)
    {
        $this->_extern_database_conn = $extern_database_conn;
    }
*/
    public function Update()
    {
        foreach($this->object_array as $obj)
        {
            $this->DoUpdateByObject($obj);
        }
    }

    public function DoUpdateByObject($obj)
    {
        $this->DoUpdateByRekursivation($obj);
    }

    public function DoUpdateBySingleObject($object) //Update nur dieses eine Object
    {
        $this->DoUpdate($object);
    }

    //Private ###################################################
    private function DoUpdateByRekursivation($object)
    {
        $this->DoUpdate($object);

        $object_vars = get_object_vars($object);

        foreach($object_vars as $key => $var)
        {
            if(is_object($var) && SHC::EndsWith("_object",$key) == false)
            {
                $this->DoUpdate($var);
            }
            elseif(is_array($var))
            {
                foreach($var as $v)
                {
                    $this->DoUpdateByRekursivation($v);
                }
            }
        }
    }

    private function DoUpdate($obj, $is_check_update_object = true) //is_check_update_object = wenn false nicht prüfen ob update_object vorhanden.
    {
        if($is_check_update_object) //prüfe ob update_object vorhanden
        {
            if($this->CheckDataUpdateObject($obj) == false)
            {
                return null;
            }
        }

        //Namensfindung und Generierung
        if($this->system_extern_function instanceof System_Extern_Function){}

        $table_name = $this->system_extern_function->GetTableNameByObject($obj);//User_Order//strtolower($obj_name); //user_order
        $where_field = $this->system_extern_function->GetIdNameByObject($obj);
        $where_id = $this->system_extern_function->GetIdByObject($obj);

        //Erhalte join
        //$join = $system_extern_function->GetJoinByDbName($table_name);
        $columnstring = $this->GetColumnStringByObject($obj);

        $query = "UPDATE {$table_name} SET {$columnstring} WHERE {$table_name}.{$where_field}='{$where_id}' "; //{$join} LIMIT 1 {$join}

        $this->_extern_database_conn->Query($query);

        //echo $this->_extern_database_conn->GetQueryString();
        //echo $this->_extern_database_conn->GetError();

        if($this->_extern_database_conn->IsAffected())
        {
            //$this->_update_check[] = true;
            //echo "success";
        }
        else
        {
            //$this->_update_check[] = "Nicht gespeichert!: ".$this->obj_name." ";
            //echo "kein Update";
        }
    }

    private function GetColumnStringByObject($object)
    {
        if($this->system_extern_function instanceof System_Extern_Function){}

        $id_name = $this->system_extern_function->GetIdNameByObject($object);

        $cols = array(); //column array wird später imploded
        $object_vars = get_object_vars($object); //bekomme alle objekt variablen

        foreach($object_vars as $key => $value) // Durchlaufe update_data_object
        {
            if(is_object($value) == false && is_array($value) == false && SHC::EndsWith("_object",$key) == false && $key!=$id_name)
            {
                $cols[] =  $key."="."'{$value}'";
            }
        }

        return implode(",",$cols);
    }

    private function CheckDataUpdateObject($object) //gibt true zurück wenn Update_Data_Object enthalten
    {
        $object_vars = get_object_vars($object); //bekomme alle objekt variablen

        foreach($object_vars as $value) // Durchlaufe update_data_object
        {
            if($value instanceof Update_Data_Object)
            {
                return true;
            }
        }

        return false;
    }




/*
 *
 *
    private function GetColumnStringByUpdateObject($object) //durchsuche nach update_object... unused
    {
        $cols = array(); //column array wird später imploded
        $object_vars = get_object_vars($obj); //bekomme alle objekt variablen

        $where_id = $object_vars[$where_field]; //Bestimme $where_id

        foreach($obj->update_data_object->update_data_container as $update_data) // Durchlaufe update_data_object
        {
            $key = $update_data->key;
            $value = $object_vars[$update_data->key];

            $cols[] =  $key."="."'{$value}'";
        }

        $colstring = implode(",",$cols);
    }

*  if($key==$where_field)
    {
        $where_id = $val;
    }
 *
 */


} 