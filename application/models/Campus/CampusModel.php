<?php
class CampusModel extends CI_Model
{
   
    public function campus()
    {
        //create campus function fror retrive data;
     $query = $this ->db ->get('m_ashram');
     return $query->result_array();
   }

}

?>
