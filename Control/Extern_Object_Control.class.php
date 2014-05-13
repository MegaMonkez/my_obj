<?php


namespace _extern_object\Control;


use _conn\Extern_Base\Extern_Base;
use _data_element\Element\Project_Object;
use _data_element\Element\Update_Data_Object;
use _extern_object\Method\Extern_Object_Add;
use _extern_object\Method\Extern_Object_Remove;
use _extern_object\Method\Extern_Object_Update;


class Extern_Object_Control extends Extern_Base
{
    //Backend:
    //User fügt Kategorie hinzu.
    //Neues Kategorie Objekt erhält update_object type=add + update_hash zur eindeutigen identifizierung
    //Parent Objekt bekommt referenz zu diesem neuen Kategorie Objekt
    //User klickt auf Speichern,
    //Objekt verhindert weitere Manipulationen des Objektes
    //Und sendet komplettes Objekt an den Server,

    //PHP:
    //Erzeuge PHP Objekte
    //Durchlaufe ankommendes Objekt rekursiv
    //Wenn update_object, type = update, packe es in update_object_array, suche weiter
    //Wenn update_object, type = add, packe es in update_add_array, keine weitere suche

    //extra function: handle nun anhand update_object_array und update_add_array Object
    //Füge hinzu, bzw. update.
    //Bei add, gebe Context_Reference_Object zurück,
    //Context_Reference_Object enthält, update_hash + object

    //Context_Output_Object
    //beinhaltet context_reference_object_container
    //status

    //Parent Objekt hat im Backend alle add objecte,




    //USAGE:
    //$ctrl = new Extern_Object_Control();
    //$ctrl->HandleObject($obj);
    //$proj_obj = $ctrl->GetProjectObject();
    //$ctrl->Set



    public $add_array = array();
    public $update_array = array();
    public $remove_array = array();

    private $project_object = null;

    public $is_debug = false;

    public function Handle_Object($object)
    {
        $this->DoRek($object);
        //$ext_db_conn = $this->GetExternDataBaseConnection();
        //if($ext_db_conn!=null)
        //{
        //    $this->Execute($ext_db_conn);
        //}
    }

    private function DoRek($object) //Füllt Arrays
    {
        $object_vars = get_object_vars($object);

        foreach($object_vars as $var)
        {
            if(is_object($var))
            {
                //echo "GO+";

                if($var instanceof Update_Data_Object)
                {
                    if($var->update_type == "UPDATE")
                    {
                        $this->update_array[] = $object;
                    }
                    else if($var->update_type == "ADD")
                    {
                        $this->add_array[] = $object;
                        return null; //Wenn add objekt beende durchlauf für aktuelles object
                    }
                    else if($var->update_type == "REMOVE")
                    {
                        $this->remove_array[] = $object;
                        return null;
                    }
                }
                else if($var instanceof Project_Object)
                {
                    $this->project_object = $var;
                }

                $this->DoRek($var);
            }
            else if(is_array($var))
            {
                foreach($var as $el)
                {
                    $this->DoRek($el);
                }
            }
        }
    }

    public function GetProjectObject() //Prüft ob unterschiedliche Projekte, sollte eing. nicht passieren
    {
        if($this->project_object!=null)
        {
            if($this->project_object instanceof Project_Object)
            {
                return $this->project_object;
            }
        }

        return null;
    }

    public function Execute($extern_database)
    {
        $extern_add = new Extern_Object_Add($extern_database);
        $extern_update = new Extern_Object_Update($extern_database);
        $extern_remove = new Extern_Object_Remove($extern_database);

        foreach($this->add_array as $add_obj)
        {
            $extern_add->AddByObject($add_obj);
        }
        foreach($this->update_array as $update_obj)
        {
            $extern_update->DoUpdateByObject($update_obj);
        }
        foreach($this->remove_array as $remove_obj)
        {
            $extern_remove->RemoveByObject($remove_obj);
        }
        //DEBUG #####
        if($this->is_debug)
        {
            $obj = array($extern_add,$extern_update,$extern_remove);

            foreach($obj as $extern)
            {

                $query_array = $extern->GetQueryArray();
                $error_array = $extern->GetErrorArray();

                if(empty($query_array)==false)
                {
                    print_r($query_array);
                }
                if(empty($error_array)==false)
                {
                    print_r($error_array);
                }
            }

        }
    }


}