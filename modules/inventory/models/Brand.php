<?php
require_once __DIR__ . '/../../../core/Model.php';

class Brand extends Model {
    protected $table = 'brands';
    protected $tenantColumn = 'tenant_id';
}
