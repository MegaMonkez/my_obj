<?php

namespace _extern_object\Method;


use _data_element\Element\Order_Element;
use _data_element\Element\System_Work;
use _extern_object\Base\Extern_Object_Base;
use _extern_object\Func\System_Extern_Function;
use _php_base\Helper\SHC;


class Extern_Object_Add extends Extern_Object_Base
{
    private $system_extern_function = null;

    public function AddByObject($object)
    {


        $this->system_extern_function = new System_Extern_Function();

        if($this->system_extern_function instanceof System_Extern_Function)
        {}

        //1:
        //Durchlaufe Objekte
        //Jedes Einzelobjekt wird geprüft ob IsTable
        //Einzelobjekt wird in datenbank geschrieben
        //Einzelobject wird einzeln SELECTed
        //An Objekt kinder wird object_id übergeben, damit diese den Bezug nicht verlieren
        //Wenn Object system_work implementiert, schreibe work_id global, und rufe ab sobald system_work an der Reihe
        //original_id bleibt erhalten.

        //2: DoAdd($object)
        //durchlaufe alle Child Objekte
        //Adde alle Objekte einzeln,

        //SELECTe dieses einzelne Objekt, ehm nein, weil GetLastInsertedId
        //Rufe SetElementByRow auf, und fülle dieses damit.

        //Nehme aus jedem child object object_id und ersetze in eigenem Object,
        //system_work erhält noch work_id
        //system_work state wird nach dem Original gesetzt, wenn state ORIGINAL, dann WORK. Wenn WORK, dann CREATED
        //update alle objekte

        //container:
        //durchlaufe alle container und setze parent_id
        //user_order_element Object kommt in DoAdd, wird geaddet, aktualisiert. Objektreferenz bleibt ja erhalten.

        //STATE: Wenn $state = empty, state = ORGINAL, wenn state


        $this->DoAdd($object);
    }

    private $state = null;

    public function SetState($state)
    {
        $this->state = $state;
    }

    public $last_inserted_id = null; //id des als allererstes hinzugefügten Objektes

    private function DoAdd($object)
    {
        $object_vars = get_object_vars($object);

        foreach($object_vars as $key => $var)
        {
            if(is_object($var))
            {
                $id_field = $key."_id";
                $object->$id_field = $this->DoAdd($var);
            }
        }

        $last_id = $this->AddSingleObject($object);

        $this->DoContainer($object);

        return $last_id;
    }

    private function DoContainer($object)
    {
        if($this->system_extern_function instanceof System_Extern_Function){}

        $id_name = $this->system_extern_function->GetIdNameByObject($object);
        $id = $this->system_extern_function->GetIdByObject($object);

        $object_vars = get_object_vars($object);

        foreach($object_vars as $key => $var)
        {
            if(is_array($var)) //var ist container
            {
                foreach($var as $v) //durchlaufe container
                {
                    $v->$id_name = $id; //übergebe id an $object

                    $this->AddAll($v);
                    $this->AddSingleObject($v);
                }
            }
        }
    }



    private function AddContainer($object) //Fügt Container Elemente hinzu.
    {
        $object_vars = get_object_vars($object);

        foreach($object_vars as $var)
        {
            if(is_array($var))
            {
                foreach($var as $v)
                {

                    $this->SwitchSingleObjectId($v,$object); //Tausche Id vorher aus

                    $this->DoAdd($v);
                }
            }
        }
    }

    private function SwitchParentId($object) //Durchläuft objekte und ruft einzeln SwitchSingleObjectId auf.
    {
        $object_vars = get_object_vars($object); //bekomme alle objekt variablen

        foreach($object_vars as $var)
        {
            if(is_object($var))
            {
                $this->SwitchSingleObjectId($object,$var);

                $this->SwitchParentId($var);
            }
        }

    }

    private function SwitchSingleObjectId($target, $source) //Tauscht die neue child_id im parent aus.
    {
        if($this->system_extern_function instanceof System_Extern_Function){}

        $child_id_name = $this->system_extern_function->GetIdNameByObject($source);

        $target->$child_id_name = $source->$child_id_name; //Tausche aus
    }

    private function AddAll($object) //Durchläuft alle Objekte, fügt jedes einzelne Objekt hinzu.
    {
        if($this->system_extern_function instanceof System_Extern_Function){}

        $object_vars = get_object_vars($object); //bekomme alle objekt variablen

        foreach($object_vars as $var)
        {
            if(is_object($var)) //&& SHC::EndsWith("_object",)
            {
                $child_id_name = $this->system_extern_function->GetIdNameByObject($var);

                $id = $this->AddSingleObject($var);

                $object->$child_id_name = $id;

                if($this->IsLink($object)) //Wenn aktuelles objekt _link Objekt, dann keine Kinder hinzufügen
                {
                    $this->AddAll($var);
                }
            }
        }
    }

    private function SetSystemWorkOrderElement($object, $last_id)
    {
        $order_element = $this->FindOrderElement($object);
        if($order_element!=null)
        {
            $this->SetOrderElement($order_element);
        }


        $system_work = $this->FindSystemWork($object);

        if($system_work!=null)
        {
            if($order_element instanceof Order_Element)
            {
                if($order_element->state == "ORIGINAL") //Dieses Objekt ist das ORIGINAL also setze original_id
                {
                    $this->SetSystemWork($system_work,$last_id,null);
                }
                else //Dieses Objekt ist nicht original, also muss ein Original vorhanden sein.
                {
                    $this->SetSystemWork($system_work,null,$last_id);
                }
            }
        }

    }


    private function SetOrderElement(Order_Element $order_element)
    {
        if($order_element!=null)
        {
            if($order_element->state==null)
            {
                $order_element->state = "ORIGINAL";
            }
            else if($order_element->state == "WORK")
            {
                $order_element->state = "CREATED";
            }
            else if($order_element->state == "ORIGINAL")
            {
                $order_element->state = "WORK";
            }
        }
    }

    private function SetSystemWork($system_work, $original_id, $work_id) //$original_id,$work_id
    {

        if($system_work!=null)
        {
            if($system_work instanceof System_Work)
            {
                $system_work->original_id = $original_id;
                $system_work->work_id = $work_id;
            }
        }
    }

    private function FindOrderElement($object)
    {
        $object_vars = get_object_vars($object); //bekomme alle objekt variablen

        foreach($object_vars as $var)
        {
            if(is_object($var))
            {
                if($var instanceof Order_Element)
                {
                    return $var;
                }

                $this->FindSystemWork($var);
            }
        }

        return null;
    }

    private function FindSystemWork($object) //Prüft und handlet falls Objekt system_work
    {
        $object_vars = get_object_vars($object); //bekomme alle objekt variablen

        foreach($object_vars as $var)
        {
            if(is_object($var))
            {
                if($var instanceof System_Work)
                {
                    return $var;
                }

                $this->FindSystemWork($var);
            }
        }

        return null;
    }


    private function AddSingleObject($single_object) //single_object ist einzelnes Objekt ohne Kinder
    {
        $table_name = $this->GetTableNameByObject($single_object);

        if(SHC::EndsWith("_object",$table_name)==false)
        {
            $id_name = $this->system_extern_function->GetIdNameByObject($single_object);

            $columns = $this->GetColumnStringByObject($single_object);

            $query = "INSERT INTO {$table_name} SET {$columns}  ";

            $this->query_array[] = $query;

            $this->_extern_database_conn->Query($query);

            $last_inserted_id = $this->_extern_database_conn->GetLastInsertedId();

            $single_object->$id_name = $last_inserted_id;

            if($this->_extern_database_conn->GetError()!="")
            {
                //echo $this->system_extern_object->GetTableNameByObject($single_object)."  ".$this->_extern_database_conn->GetError();
                $this->error_array[] = $this->_extern_database_conn->GetError();
            }
        }
        return $last_inserted_id;

    }

    private function GetColumnStringByObject($object)
    {
        $cols = array(); //column array wird später imploded
        $object_vars = get_object_vars($object); //bekomme alle objekt variablen

        foreach($object_vars as $key => $var) // Durchlaufe update_data_object
        {
            //Teste ob daten column
            if(is_object($var)==false && is_array($var)==false && $this->IsTable($key)==false && $key!=$this->system_extern_function->GetIdNameByObject($object) && SHC::EndsWith("_object",$key)==false)
            {
                $cols[] =  $key."="."'{$var}'";
            }
        }

        $colstring = implode(",",$cols);

        return $colstring;
    }

    private function IsLink($object) //Prüft ob object link object ist
    {
        $table_name = $this->GetTableNameByObject($object);

        if(SHC::EndsWith("_link",$table_name))
        {
            return true;
        }
        else
        {
            return false;
        }
    }


    /*
     *
     private function DoAddOld($object)
    {
        $last_id = $this->AddSingleObject($object);

        if($this->last_inserted_id == null)
        {
            $this->last_inserted_id = $last_id;
        }

        //$this->SetSystemWorkOrderElement($object,$last_id);

        $this->AddAll($object);

        $this->AddContainer($object);
    }
     */

}