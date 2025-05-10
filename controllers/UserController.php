<?php
require_once '../models/UserModel.php';

class UserController {
    public function show($id) {
        $model = new UserModel();
        $user = $model->getUser($id);
        include '../views/userView.php';
    }
}
?>