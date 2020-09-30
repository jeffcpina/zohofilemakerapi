<?php
namespace Src\Controller;
use \Verot\Upload\Upload;
use \jeffcpina\fmRest\fmREST;

class DocController {

    private $requestMethod;

    public function __construct($requestMethod)
    {
        $this->requestMethod = $requestMethod;
        $this->fm_config_data();
    }

    public function processRequest()
    {
        switch ($this->requestMethod) {
            case 'GET':
                $response = $this->unprocessableEntityResponse();
                break;
            case 'POST':
                $response = $this->processDocument();
                break;
            case 'PUT':
                $response = $this->unprocessableEntityResponse();
                break;
            case 'DELETE':
                $response = $this->unprocessableEntityResponse();
                break;
            default:
                $response = $this->notFoundResponse();
                break;
        }
        header($response['status_code_header']);
        if ($response['body']) {
            echo $response['body'];
        }
    }

    private function processDocument(){
        //init
        $file_status = false;
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $result ="";

        //get post fields
        $filename = $_POST["filename"];
        $format = $_POST["format"];

        //retrieve default upload folder
        $dir = __DIR__;
        $dir = str_replace("documents/src/Controller","",$dir);
        $upload_dir  = $dir . "uploads/";

        //process upload
        $handle = new Upload($_FILES['content']);

        if ($handle->uploaded) {
            $handle->file_new_name_body = $filename;
            $handle->file_overwrite = true;
            $handle->file_new_name_ext = '';
            $handle->file_force_extension = false;
            $handle->process($upload_dir);
            if ($handle->processed) {
                $result =  "Document '$filename' saved";
                $handle->clean();
                $db = $this->getFmDatabase("Raw Website Users");
            } else {
                $result = 'error : ' . $handle->error;
            }
        }

        $response['body'] = $result;

        return $response;
    }

    private function unprocessableEntityResponse()
    {
        $response['status_code_header'] = 'HTTP/1.1 422 Unprocessable Entity';
        $response['body'] = json_encode([
            'error' => 'Invalid input'
        ]);
        return $response;
    }

    private function notFoundResponse()
    {
        $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
        $response['body'] = null;
        return $response;
    }
    function getFmDatabase($layout)
    {
        //$layout = "Raw Website Users";
        if ($layout=="") throw new exception("No layout found");
        $this->db_table = new fmREST ($this->host, $this->db, $this->user, $this->pass, $layout);
        $this->db_table -> show_debug = false; //turn this to true or "html" to show automatically. We're manually including debug information with <print_r ($fm->debug_array);>
        $this->db_table -> secure = false; //not required - defaults to true
        return $this->db_table;
    }
    function fm_config_data(){
        $host = $this->host = 'fms.reachmakers.com';
        $db = $this->db = 'Bugly';
        $user = $this->user = 'admin';
        $pass = $this->pass = 'rtgi01';
    }
}
