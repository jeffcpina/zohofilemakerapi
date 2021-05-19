<?php
namespace Src\Controller;
use \Verot\Upload\Upload;
use \jeffcpina\fmRest\fmREST;

class DocController {

    private $requestMethod;

    private $host;
    private $name;
    private $user;
    private $pass;
    private $container;
    private $fm_db;


    public function __construct($requestMethod,$user_layout = "",$container="")
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        echo "$user_layout \n\r";

        $path = (isset($_POST["fm_path"])) ? $_POST["fm_path"] . "_" : "";
        $path = strtoupper($path);

        $this->fm_config_data($container);
        $this->requestMethod = $requestMethod;

        //layout and container precedence: post, call then env
        $user_layout = (isset($_POST["fm_layout"])) ? $_POST["fm_layout"] :
                       (($user_layout != "")        ? $user_layout
                                                    : $_ENV[$path ."FM_LAYOUT"]);
        $this->container = (isset($_POST["fm_container"])) ? $_POST["fm_container"] :
                           (($container != "")             ? $container
                                                           : $_ENV[$path ."FM_CONTAINER"]);

        $this->fm_db = $this->getFmDatabase($user_layout);

        //$this->show_initial_data();
    }
    public function show_initial_data(){
        echo "Initial Data \n\r Post \n\r";
        print_r($_POST);
        print_r($this->fm_db);
        echo $this->container . "\n\r";
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
        echo "you got here, now what.";
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
        $path = (isset($_POST["fm_path"])) ? $_POST["fm_path"] : "";

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
                    $result = $this->upload_container_to_fm ($record_id, $_FILES['content'], $this->container);
                    $result = ($result == "OK") ? "Document '$filename' saved" : $result;
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
        if ($layout=="") throw new exception("No layout found");
        $table = new fmREST ($this->host, $this->name, $this->user, $this->pass, $layout);
        $table -> show_debug = false; //turn this to true or "html" to show automatically. We're manually including debug information with <print_r ($fm->debug_array);>
        $table -> secure = false; //not required - defaults to true
        return $table;
    }
    function fm_config_data(){
        $path = (isset($_POST["fm_path"])) ? $_POST["fm_path"] . "_" : "";
        $path = strtoupper($path);
        $this->host = $_ENV[$path . "FM_HOST"];
        $this->name = $_ENV[$path . 'FM_DB'];
        $this->user = $_ENV[$path . 'FM_USER'];
        $this->pass = $_ENV[$path . 'FM_PASS'];
    }
    function save_data_to_fm($table_data)
    {
        $data['fieldData'] = $table_data; //print_r($data);

        $result = $this->fm_db -> createRecord ( $data ) ; //print_r($result);
        if ($result['messages'][0]['message'] != "OK") $this->send_error_log($data,$result);
        return $result;
    }
    function upload_container_to_fm($fm_id, $file, $container, $lookup = false)
    {
        $ready = false;

        //only if you need to find real filemaker id
        if ($lookup) {
            //find records
            $fm_id = $this->find_internal_fm_id($fm_id);
            $ready = ($fm_id != "Error") ? true : false;
        }
        else {
            $ready = true;
        }

        if ($ready) {
            $response = $result = $this->fm_db -> uploadContainer ($fm_id, $container, $file );
        }
        if (isset($result["messages"][0]["code"])) {
            if ($result["messages"][0]["code"] == 0) {
                $result = "OK";
            }
            else {
                $response = $result;
                $response["source"] = $this->fm_db;
                $code = $result["messages"][0]["code"];
                $result = "Document did not save. Please contact your IT admin. Code: fm-$code";
                $result = $result . print_r($response, true);
            }
        }
        else $result = "Document did not save. Please contact your IT admin. Code: sc-2022";

        return $result;
    }
    function find_internal_fm_id($fm_id)
    {
        //find records
        $request1['id'] = $fm_id;
        $query = array($request1);
        $data['query'] = $query;
        $data['limit'] = 1;
        $result = $this->fm_db->findRecords($data);
        //print_r($result);
        //get internal filemaker id
        if ($result["messages"][0]["code"] == 0) {
            $fm_id = $result["response"]["data"][0]["recordId"];
        }
        else{
            $fm_id = "Error";
        }
        //print_r($result);
        return $fm_id;
    }
}
