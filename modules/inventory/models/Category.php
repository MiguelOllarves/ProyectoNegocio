<?php
require_once __DIR__ . '/../../../core/Model.php';

class Category extends Model {
    protected $table = 'categories';
    protected $tenantColumn = 'tenant_id';
}
