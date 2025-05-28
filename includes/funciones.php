<?php

function debuguear($variable)
{
    echo "<pre>";
    var_dump($variable);
    echo "</pre>";
    exit;
}

// Escapa / Sanitizar el HTML
function s($html)
{
    $s = htmlspecialchars($html);
    return $s;
}

// Función que revisa que el usuario este autenticado
function isAuth()
{
    session_start();
    if (!isset($_SESSION['login'])) {
        header('Location: /');
    }
}
function isAuthApi()
{
    getHeadersApi();
    session_start();
    if (!isset($_SESSION['auth_user'])) {
        echo json_encode([
            "mensaje" => "No esta autenticado",

            "codigo" => 4,
        ]);
        exit;
    }
}

function isNotAuth()
{
    session_start();
    if (isset($_SESSION['auth'])) {
        header('Location: /auth/');
    }
}


function hasPermission(array $permisos)
{

    $comprobaciones = [];
    foreach ($permisos as $permiso) {

        $comprobaciones[] = !isset($_SESSION[$permiso]) ? false : true;
    }

    if (array_search(true, $comprobaciones) !== false) {
    } else {
        header('Location: /');
    }
}

function hasPermissionApi(array $permisos)
{
    getHeadersApi();
    $comprobaciones = [];
    foreach ($permisos as $permiso) {

        $comprobaciones[] = !isset($_SESSION[$permiso]) ? false : true;
    }

    if (array_search(true, $comprobaciones) !== false) {
    } else {
        echo json_encode([
            "mensaje" => "No tiene permisos",

            "codigo" => 4,
        ]);
        exit;
    }
}

// function getHeadersApi(){
//     return header("Content-type:application/json; charset=utf-8");
// }

function getHeadersApi()
{
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
}

function asset($ruta)
{
    return "/" . $_ENV['APP_NAME'] . "/public/" . $ruta;
}
