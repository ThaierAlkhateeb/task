<?php

require '../vendor/autoload.php';

use \Psr\Http\Message\RequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'db.php';

$dbConfig = array(
    'host' => 'localhost',
    'dbName' => 'mediaopt',
    'dbUser' => 'root',
    'dbPassword' => ''
);

$app = new \Slim\App(array("MODE" => "developement"));
$db = new Database($dbConfig);

$app->post('/login', function (Request $request, Response $response, $args) use ($app, $db) {
    $apiResponse = array();
    $username = $request->getParam('username');
    $password = $request->getParam('password');
    if (!empty($username) && !empty($password)) {
        $code = 200;
        $apiResponse = $db->login($username, $password);
    } else {
        $code = 401;
        $apiResponse['status'] = 'fail';
        $apiResponse['message'] = 'Please enter required details';
    }
    return $response->withJson($apiResponse, $code);
});

$app->post('/logout', function (Request $request, Response $response, $args) use ($app, $db) {
    $apiResponse = array();
    $token = $request->getParam('token');
    if (!empty($token)) {
        $code = 200;
        $apiResponse = $db->logout($token);
    } else {
        $code = 401;
        $apiResponse['status'] = 'fail';
        $apiResponse['message'] = 'Invalid Token';
    }
    return $response->withJson($apiResponse, $code);
});


$app->get('/upload', function () use ($app) {
    echo '
        <!DOCTYPE html>
        <html>
        <head>
            <title>CSV Upload</title>
        </head>
        <body>
            <p>Please upload the csv.</p>
            <p>The file format : employee_id , project_id , login_time , logout_time</p>
            <form action="" enctype="multipart/form-data" method="post">
                <input type="file" name="csv" /><br/>
                <input type="submit" value="Upload Now"/>
            </form>
        </body>
        </html>';
});

$app->post('/upload', function () use ($app, $db) {

    if (!isset($_FILES['csv'])) {

        echo "No file uploaded!! <br /><a href=''>Reupload</a>";
        return;
    }
    if ($_FILES['csv']['error'] === 0) {
        $name = uniqid('csv-' . date('Ymd') . '-');
        $ext = pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES['csv']['tmp_name'], 'uploads/' . $name . '.' . $ext) === true) {
            if ($ext == 'csv') {
                $csv = 'uploads/' . $name . '.' . $ext;
                $db->printCsv($csv);
                $db->saveCsv($csv);
            } else {
                echo "File not supported";
            }
        }
    }
});

$app->get('/project', function (Request $request, Response $response, $args) use ($app, $db) {
    $apiResponse = array();
    $projectId = $request->getParam('id');
    if (!empty($projectId)) {
        $code = 200;
        $apiResponse = $db->projectHours($projectId);
    } else {
        $code = 401;
        $apiResponse['status'] = 'fail';
        $apiResponse['message'] = 'Please enter required details';
    }
    return $response->withJson($apiResponse, $code);
});

$app->get('/statistic', function (Request $request, Response $response, $args) use ($app, $db) {
    $apiResponse = array();
    $day = $request->getParam('day');
    $projectId = $request->getParam('project');

    if (!empty($day) && !empty($projectId)) {
        $code = 200;
        $apiResponse = $db->peakTime($day, $projectId);
    } else {
        $code = 401;
        $apiResponse['status'] = 'fail';
        $apiResponse['message'] = 'Please enter required details';
    }
    return $response->withJson($apiResponse, $code);
});

$app->run();
?>