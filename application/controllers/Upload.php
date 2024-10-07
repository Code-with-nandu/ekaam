<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Upload extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->helper('file'); // Load the file helper
        $this->load->library('upload'); // Load the upload library
        $this->create_upload_directory(); // Ensure the uploads directory exists
    }

    private function create_upload_directory() {
        $upload_path = FCPATH . 'uploads/';
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, true); // Create directory with appropriate permissions
        }
    }

    

    // Method to handle the chunked file upload
    public function chunk_upload() {
        // Get the unique file identifier and chunk number from Dropzone
        $fileName = $this->input->post('dzuuid') . ".part";
        $chunk = $this->input->post('dzchunkindex');
        $totalChunks = $this->input->post('dztotalchunkcount');
        $filePath = FCPATH . 'uploads/' . $fileName;

        // Open the file in append mode
        if (!$out = fopen($filePath, 'ab')) {
            show_error('Could not open file for writing.', 500);
        }

        // Read the incoming chunk
        if (!$in = fopen($_FILES['file']['tmp_name'], 'rb')) {
            show_error('Could not open input file.', 500);
        }

        // Append the chunk to the file
        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }
        fclose($in);
        fclose($out);

        // If this is the last chunk, rename the file
        if ($chunk == $totalChunks - 1) {
            $finalFileName = $this->input->post('dzuuid') . "_" . $_FILES['file']['name'];
            rename($filePath, FCPATH . 'uploads/' . $finalFileName);
        }

        echo json_encode(['status' => 'success']);
    }
}
