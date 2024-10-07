<?php
class DatabaseCheck extends CI_Controller
{
    public function index()
    {
        // Load the default database (campus)
        $this->db = $this->load->database('default', TRUE);

        // Load the ashrams database
        $this->adb = $this->load->database('ashrams', TRUE);

        // Check if 'default' (campus) database is connected
        if ($this->db->conn_id) {
            echo "Connected to the default database (campus).<br>";

            // Fetch some data from 'campus' database (e.g., 'students' table)
            $query = $this->db->get('m_ashram'); // Adjust 'students' to your table name
            $campus_data = $query->result();

            echo "<h3>Campus Database Data:</h3>";
            foreach ($campus_data as $row) {
                echo "displayname: " . $row->id . " - Name: " . $row->displayname . "<br>";
            }
        } else {
            echo "Failed to connect to the default database (campus).<br>";
        }

        // Check if 'ashrams' database is connected
        if ($this->adb->conn_id) {
            echo "Connected to the ashrams database.<br>";

            // Fetch some data from 'ashrams' database (e.g., 'programs' table)
            $query = $this->adb->get('programs'); // Adjust 'programs' to your table name
            $ashrams_data = $query->result();

            echo "<h3>Ashrams Database Data:</h3>";
            foreach ($ashrams_data as $row) {
                echo "Program ID: " . $row->id . " - Program Name: " . $row->program_name . "<br>";
            }
        } else {
            echo "Failed to connect to the ashrams database.<br>";
        }
    }
}
?>
