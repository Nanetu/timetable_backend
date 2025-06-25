<?php

class Controller {
    protected function loadModel($model){
        require_once __DIR__ . '/../models/' . $model . '.php';
        return new $model;
    }

    protected function renderView($view_path, $data=[], $title="Timetable"){
        extract($data);
        require_once '../app/views/layout.php';
    }

    protected function setJsonHeaders(){
        header("Content-Type: application/json");
    }
}

?>