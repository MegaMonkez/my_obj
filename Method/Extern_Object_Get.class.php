<?php

namespace _extern_object\Method;

use _extern_object\Base\Extern_Object_Base;
use _extern_object\Func\System_Extern_Function;

use _php_base\Helper\SHC;
use _php_base\Object\Object_Namespace;
use _php_base\Object\Object_Rekonstructor;

class Extern_Object_Get extends Extern_Object_Base
{

    //Objekte
    private $object_rekunstructor = null;
    private $system_extern_function = null;


    public function __construct($extern_db)
    {
        $this->object_rekunstructor = new Object_Rekonstructor();
        $this->SetExternDatabaseConnection($extern_db);
    }

    //Felder
    public $is_meta = false; //Wenn true, dann nur meta daten raussuchen

    public $column_array = array();

    public function GetByObject($object, $where, $limit_from = 0, $limit_to = 9999999) //Wenn where = " " = FINDE ALLE(leerzeichen), null = Finde by Object, where = "" = FEHLER!!!
    {

        if($this->object_rekunstructor->namespace==null)
        {
            $this->object_rekunstructor->SetNamespace(Object_Namespace::DATA_ELEMENT);
        }
        $this->system_extern_function = new System_Extern_Function();

        return $this->DoRekWhere($object, $where, $limit_from, $limit_to);
    }

    public function SetNamespace($namespace)
    {
        $this->object_rekunstructor->SetNamespace($namespace);
    }


    public function AddSetColumn($column) //Bestimme die zu holenden Columns, Standard "*"
    {
        $this->column_array[] = $column;
    }

    //private ###########################################################################

    private function DoRekWhere($object, $where = " ", $limit_from = 0, $limit_to = 9999999)
    {
        $this->GenerateObject($object); //Generiere Objekt

        $join_str = $this->ObjectJoin($object); //Erhalte Join String

        $object_name = SHC::GetObjectName($object);

        $object_table_name = $this->system_extern_function->GetTableNameByObject($object);

        $column_select = "*";

        $where_befehl = " WHERE ";

        if($where == null) //Wenn $where null suche Objekt anhand Object.
        {
            $object_id = $this->system_extern_function->GetIdByObject($object);
            $object_id_name = $this->system_extern_function->GetIdNameByObject($object);

            $where = $object_table_name.".".$object_id_name." = ".$object_id;
        }
        else if($where == " ")
        {
            $where_befehl = "";
        }

        if(empty($this->column_array)==false)
        {
            $column_select = "";
            foreach($this->column_array as $column)
            {
                $column_select = $column_select." ".$column;
            }
        }

        //Query
        $query = "SELECT {$column_select} FROM {$object_table_name} {$join_str} {$where_befehl} {$where} LIMIT {$limit_from},{$limit_to}"; //"SELECT * FROM user_order_element WHERE user_order_id = $user_order_id";
        $this->query_array[] = $query;

        $result = $this->_extern_database_conn->Query($query)->ReturnArray();
        $this->error_array[] = $this->_extern_database_conn->GetError();

        //$result = $this->_extern_database_conn->Query("SELECT * FROM user_wako LEFT JOIN product_data ON user_wako.product_data_id = product_data.product_data_id LEFT JOIN user_wako_item_link ON user_wako.user_wako_item_link_id = user_wako_item_link.user_wako_item_link_id WHERE user_wako.user_wako_id  IN ( 165, 166, 167, 168, 164 ) LIMIT 0 , 9999999")->ReturnArray();
        $instance_array = array();
        foreach($result as $res)
        {
            $instance = $this->object_rekunstructor->GetNewInstanceByNameData($object_name,null);
            $this->GenerateObject($instance);
            $this->ObjectSetElementByRow($instance,$res);

            if($this->is_meta == false)
            {}

            $this->DoContainer($instance);
            $this->DoLink($instance);

            $instance_array[] = $instance;
        }

        //container
        return $instance_array; //$instance_array;
    }

    private function DoLink($object)
    {
        $object_vars = get_object_vars($object); //bekomme alle objekt variablen

        if($this->system_extern_function instanceof System_Extern_Function)
        {}

        //$table_name = $this->system_extern_function->GetTableNameByObject($object);

        foreach($object_vars as $key => $var)
        {
            if(is_object($var)) //ist object
            {
                if(SHC::EndsWith("_link",$key))
                {
                    $id_name = $this->system_extern_function->GetIdNameByObject($var);

                    $id = $var->$id_name;

                    $where = $id_name."=".$id;

                    $object->$key = $this->DoRekWhere($var,$where)[0];
                }
            }
        }
    }

    private function DoContainer($object)
    {
        //echo $this->GetTableNameByObject($object)." -----";

        if($this->system_extern_function instanceof System_Extern_Function)
        {}

        $parent_id = $this->system_extern_function->GetIdByObject($object);
        $parent_id_name = $this->system_extern_function->GetIdNameByObject($object);

        $object_vars = get_object_vars($object); //bekomme alle objekt variablen

        foreach($object_vars as $key => $var)
        {
            if(is_array($var)) //ist container?
            {
                $child_table_name = str_replace("_container","",$key); //ersetze um tabellennamen zu erhalten

                $where = $child_table_name.".".$parent_id_name." = ".$parent_id; //Generiere Where

                $obj = $this->object_rekunstructor->GetNewInstanceByNameData($child_table_name,null);

                $object->$key = $this->DoRekWhere($obj,$where);

                foreach($object->$key as $new_object)
                {
                    //echo $this->GetTableNameByObject($new_object)." -----";
                    //$this->DoObjectContainer($new_object);
                }
            }
            else if(is_object($var))
            {
                $this->DoContainer($var);
            }
        }
    }

    private function ObjectSetElementByRow($object, $result) //
    {
        $this->CallSetElementByRow($object,$result);

        $object_vars = get_object_vars($object); //bekomme alle objekt variablen

        foreach($object_vars as $var)
        {
            if(is_object($var))
            {
                $this->CallSetElementByRow($var,$result);
                $this->ObjectSetElementByRow($var,$result);
            }
        }
    }

    private function CallSetElementByRow($object,$result)
    {
        if(method_exists($object,"SetElementByRow"))
        {
            $object->SetElementByRow($result);
        }
    }

    private function GenerateObject($object) //Füllt Object mit Objekten
    {
        $object_vars = get_object_vars($object); //bekomme alle objekt variablen

        foreach($object_vars as $key => $var)
        {
            if($this->IsTable($key)) //Ist feld ein daten_element?
            {
                if(is_object($key)==false) //nicht schon zufällig ein Objekt?
                {
                    $object_name = $this->ClassNameByTable($key);

                    $obj = $this->object_rekunstructor->GetNewInstanceByNameData($object_name,null); //Erzeuge Objekt Instanz

                    $object->$key = $this->GenerateObject($obj);
                }
            }
        }

        return $object;
    }

    private function ObjectJoin($object)
    {
        $join_str = "";

        $object_vars = get_object_vars($object); //bekomme alle objekt variablen

        foreach($object_vars as $key=> $var)
        {
            if(is_object($var))
            {
                if(SHC::EndsWith("_link",$key)==false) //Kein Link Objekt ne.
                {
                    $join_str = $join_str.$this->GetJoinString($object,$var);
                    $join_str = $join_str.$this->ObjectJoin($var);
                }
            }
        }

        return $join_str;
    }

    private function GetJoinString($parent,$child) //Generiert einen teil des Join string
    {
        $parent_name = $this->system_extern_function->GetTableNameByObject($parent);
        $child_name = $this->system_extern_function->GetTableNameByObject($child);
        $child_id = $child_name."_id";

        //LEFT JOIN order_element ON user_order.order_element_id = order_element.order_element_id
        return " LEFT JOIN {$child_name} ON {$parent_name}.{$child_id} = {$child_name}.{$child_id} ";
    }

    private function ClassNameByTable($table_name)
    {
        return SHC::UppercaseWords("_",$table_name);
    }

    // OLD ###############################################################################
/*
 *

    private function DoObjectContainer($object)
    {
        $object_vars = get_object_vars($object); //bekomme alle objekt variablen

        foreach($object_vars as $key => $var)
        {
            if(is_object($var))
            {
                if(SHC::EndsWith("_object",$key)==false)
                {}
                    //$this->DoRekWhere($var,null);

                    $this->DoContainer($var);

            }
        }
    }

    private function DoJoin($object) //Erzeugt Objekte, und füllt nebenbei noch join array
    {
        $object_vars = get_object_vars($object); //bekomme alle objekt variablen
        $object_name = SHC::GetObjectName($object);

        //Erhalte table_list
        $table_list = $this->GetIdElementPairTableNameArray($object_vars);

        $created_object_container = array();
        foreach($table_list as $table)
        {
            if($this->IsTable($table)) //prüfe ob wirklich eine tabelle, ja dann erzeuge Objekt
            {
                $class_name = SHC::UppercaseWords("_",$table); //erzeuge aus tabellen namen user_order_element -> User_Order_Element

                $created_object = $this->object_rekunstructor->GetNewInstanceByNameData($class_name); //Erzeuge Objekt Instanz

                $this->DoJoin($created_object);

                $element = $table."_element"; //element name erzeugen um objekt der klasse zu übergeben
                $object->$element = $created_object; //übergebe Instanz der klasse
                $created_object_container[] = $created_object; // ab in den Container
            }
        }

        //Erzeuge join
        foreach($created_object_container as $created_object)
        {
            $this->join_arr[] = $this->GetJoinString($object,$created_object);

            //$this->DoRek($created_object); //Erzeugte Objekte durchlaufen ebenfalls Rek
        }
    }

    private $join_container = array();

    private function DoRek($object) //Muss von Element_Base erben SetElementByRow
    {
        $object_vars = get_object_vars($object); //bekomme alle objekt variablen
        $object_name = SHC::GetObjectName($object);

        //Erhalte table_list
        $table_list = $this->GetIdElementPairTableNameArray($object_vars);

        $created_object_container = array();
        foreach($table_list as $table)
        {
            if($this->IsTable($table)) //prüfe ob wirklich eine tabelle, ja dann erzeuge Objekt
            {
                $class_name = SHC::UppercaseWords("_",$table); //erzeuge aus tabellen namen user_order_element -> User_Order_Element

                $created_object = $this->object_rekunstructor->GetNewInstanceByNameData($class_name); //Erzeuge Objekt Instanz

                $this->DoRek($created_object);

                $created_object_container[] = $created_object; // ab in den Container
            }
        }

        //Erzeuge join
        foreach($created_object_container as $created_object)
        {
            $this->join_container[] = $this->GetJoinString($object,$created_object);

            $this->DoRek($created_object); //Erzeugte Objekte durchlaufen ebenfalls Rek
        }

        $join_string = implode($this->join_container);

        //Query
        $object_table_name = $this->system_extern_object->GetTableNameByObject($object);
        $query = "SELECT * FROM {$object_table_name} {$join_string} {$where}"; //"SELECT * FROM user_order_element WHERE user_order_id = $user_order_id";

        $result = $this->_extern_database_conn->Query($query)->ReturnArray();

        //Erzeuge instanzen
        $instance_array = array();
        foreach($result as $res)
        {
            $instance = $this->object_rekunstructor->GetNewInstanceByNameData($object_name,$res);

            //Erzeuge kreierte Objekte
            foreach($created_object_container as $created)
            {
                $element = $table."_element"; //element name erzeugen um objekt der klasse zu übergeben
                $object->$element = $created_object; //übergebe Instanz der klasse
            }


            $instance_array[] = $instance;

        }

        //Fülle mit result alle objekte,
        //nein da mehrere user_order, gebe an eine FillMethode
        //anhand der user_order die zurückkommen, werden Container bearbeitet und gefüllt.


        $this->join_container = array(); //zurücksetzen, denn jetzt kommen die Container!
    }

    private function HandleObjectContainer($object)
    {
        $container_array = $this->GetContainerTableArray($object);

        foreach($container_array as $table)
        {

        }
    }



    private function GetContainerTableArray($object) //Liefert tabellennamen die enden mit _container
    {
        $object_vars = get_object_vars($object); //bekomme alle objekt variablen

        $container_array = array();

        foreach($object_vars as $var)
        {
            $container_array[] = $var;
        }

        return $container_array;

    }

    private function GetIdElementPairTableNameArray($object_vars) //Erhalte array mit table_namen die _id + _element haben.
    {
        $table_list = array();
        foreach($object_vars as $key => $var)
        {
            if(SHC::EndsWith($key,"_id"))
            {
                $table_name = str_replace("_id","",$key);
                $table_element_name = $table_name."_element";

                //prüfe ob element auch vorhanden
                foreach($object_vars as $key2 => $var2)
                {
                    if($table_element_name == $key2)
                    {
                        //jepp
                        $table_list[] = $table_name;
                    }
                }
            }
        }

        return $table_list;
    }

    private function GetInstanceByTableName($table_name)
    {

    }
*/

} 