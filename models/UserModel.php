<?php
class UserModel {
    public function getUser($id) {
        return ["id" => $id, "name" => "John Doe"];
    }
}
?>