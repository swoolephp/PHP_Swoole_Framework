<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Libs\CacheRedis;

class ApiController extends Controller
{
    public function __construct() {
        header('Content-Type: application/json');
    }

    public function index()
    {
        echo "This is the default API response";
    }

    public function getData()
    {
        $cache = new CacheRedis();
        $key = 'sample_data';

        // Check if data exists in cache
        if ($cache->exists($key)) {
            $data = json_decode($cache->get($key), true);
        } else {
            // Logic for fetching data from the database or other sources
            $data = ['message' => 'This is a sample API response'];
            $cache->set($key, json_encode($data), 300);
        }

        $this->_success($data, 'Data retrieved successfully');
    }

    private function _success($data, $message = ''){
        $response = [
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    private function _error($data, $message = '', $code = 403){
        $response = [
            'status' => 'error',
            'message' => $message,
            'code'  =>  $code,
            'data' => $data
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
    }

    public function successDemo()
    {
        $data = ['message' => 'This is a successful response'];
        $this->_success($data, 'Request was successful', 200);
    }

    public function forbiddenDemo()
    {
        $data = ['message' => 'You do not have permission to access this resource'];
        $this->_error($data, 'Forbidden', 403);
    }

    public function notFoundDemo()
    {
        $this->_error(null, 'Resource not found', 404);
    }

    public function internalErrorDemo()
    {
        $this->_error(null, 'Internal server error', 500);
    }
}
