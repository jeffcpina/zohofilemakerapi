<?php
namespace Src\Controller;
use \Verot\Upload\Upload;
use \jeffcpina\fmRest\fmREST;

class DocController {

    private $requestMethod;

    //todo move to fmRest config file.
    private $host;
    private $db;
    private $user;
    private $pass;


    public function __construct($requestMethod)
    {
        $this->fm_config_data();
        $this->requestMethod = $requestMethod;

    }

    public function processRequest()
    {
        switch ($this->requestMethod) {
            case 'GET':
                $response = $this->test_data();
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

    private function test_data(){
        echo "here";

        die;
    }

    private function processDocument(){
        //init
        $file_status = false;
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $result ="";

        //get post fields
        $filename = $_POST["filename"];
        $format = $_POST["format"];
        $record_id = $_POST["fm_id"];

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
                try {
                    $result = $this->upload_container_to_fm ($record_id, $_FILES['content'], "RAW Test Table", false );
                    $result = ($result == "OK") ? "Document '$filename' saved for $record_id" : $result;
                }
                catch (Exception $e){
                    $result = $e;
                }

                $handle->clean();

            } else {
                $result = 'error : ' . $handle->error;
            }
        }
        //*/
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
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        //$layout = "Raw Website Users";
        if ($layout=="") throw new exception("No layout found");
        $table = new fmREST ($this->host, $this->db, $this->user, $this->pass, $layout);
        $table -> show_debug = false; //turn this to true or "html" to show automatically. We're manually including debug information with <print_r ($fm->debug_array);>
        $table -> secure = false; //not required - defaults to true
        return $table;
    }
    function fm_config_data(){
        $this->host = $_ENV["FM_HOST"];
        $this->db = $_ENV['FM_DB'];
        $this->user = $_ENV['FM_USER'];
        $this->pass = $_ENV['FM_PASS'];
    }
    function save_data_to_fm($table_data, $user_layout)
    {
        $db = $this->getFmDatabase($user_layout);
        $data['fieldData'] = $table_data; //print_r($data);

        $result = $db -> createRecord ( $data ) ; //print_r($result);
        if ($result['messages'][0]['message'] != "OK") $this->send_error_log($data,$result);
        return $result;
    }
    function upload_container_to_fm($fm_id, $file, $user_layout, $lookup = true)
    {
        $ready = false;
        $db = $this->getFmDatabase($user_layout);

        //need to find real filemaker id
        if ($lookup) {
            //find records
            $request1['id'] = $fm_id;
            $query = array($request1);
            $data['query'] = $query;
            $data['limit'] = 1;
            $result = $db->findRecords($data);

            //get internal filemaker id
            if (isset($result["messages"]["code"])) {
                $fm_id = $result["response"]["data"][0]["recordId"];
                $ready = true;
            }
        }
        else {
            $ready = true;
        }

        if ($ready) {
            $result = $db -> uploadContainer ($fm_id, 'Document_Container', $file );
        }
        if (isset($result["messages"][0]["code"])) {
            if ($result["messages"][0]["code"] == 0) {
                $result = "OK";
            }
            else {
                $code = $result["messages"][0]["code"];
                $result = "Document did not save. Please contact your IT admin. Code: $code";
            }
        }
        else $result = "Document did not save. Please contact your IT admin. Code: 2022";

        return $result;
    }

}
