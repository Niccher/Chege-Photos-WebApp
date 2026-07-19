<?php
namespace App\Controllers\Api;
use App\Controllers\BaseController;

class TestController extends BaseController {
    public function counts() {
        return $this->response->setJSON($this->getSidebarCounts());
    }
}
