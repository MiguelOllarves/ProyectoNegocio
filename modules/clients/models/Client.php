<?php
require_once __DIR__ . '/../../../core/Model.php';
class Client extends Model {
    protected $table = 'clients';
    protected $tenantColumn = 'tenant_id';
}
