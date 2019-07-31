<?php

namespace Tests;

use HipsterJazzbo\Landlord\BelongsToTenants;
use HipsterJazzbo\Landlord\Facades\Landlord;
use HipsterJazzbo\Landlord\TenantManager;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class LandlordTest extends TestCase
{
    public function testTenantsWithStrings()
    {
        $landlord = new TenantManager();

        $landlord->addTenant('tenant_a_id', 1);

        $this->assertEquals(['tenant_a_id' => 1], $landlord->getTenants()->toArray());

        $landlord->addTenant('tenant_b_id', 2);

        $this->assertEquals(['tenant_a_id' => 1, 'tenant_b_id' => 2], $landlord->getTenants()->toArray());

        $landlord->removeTenant('tenant_a_id');

        $this->assertEquals(['tenant_b_id' => 2], $landlord->getTenants()->toArray());

        $this->assertTrue($landlord->hasTenant('tenant_b_id'));

        $this->assertFalse($landlord->hasTenant('tenant_a_id'));
    }

    public function testTenantsWithModels()
    {
        Landlord::shouldReceive('applyTenantScopes');

        $tenantA = new TenantA();

        $tenantA->id = 1;

        $tenantB = new TenantB();

        $tenantB->id = 2;

        $landlord = new TenantManager();

        $landlord->addTenant($tenantA);

        $this->assertEquals(['tenant_a_id' => 1], $landlord->getTenants()->toArray());

        $landlord->addTenant($tenantB);

        $this->assertEquals(['tenant_a_id' => 1, 'tenant_b_id' => 2], $landlord->getTenants()->toArray());

        $landlord->removeTenant($tenantA);

        $this->assertEquals(['tenant_b_id' => 2], $landlord->getTenants()->toArray());

        $this->assertTrue($landlord->hasTenant('tenant_b_id'));

        $this->assertFalse($landlord->hasTenant('tenant_a_id'));
    }

    public function testApplyTenantScopes()
    {
        $landlord = new TenantManager();

        $landlord->addTenant('tenant_a_id', 1);

        $landlord->addTenant('tenant_b_id', 2);

        Landlord::shouldReceive('applyTenantScopes');

        $model = new ModelStubWithBelongsToOneTenant();

        $landlord->applyTenantScopes($model);

        foreach ($model->getGlobalScopes() as $globalScope) {
            $this->assertEquals($globalScope->getTenantColumn(), 'tenant_a_id');
        }
    }

    public function testNewModel()
    {
        $landlord = new TenantManager();

        $landlord->addTenant('tenant_a_id', 1);

        $landlord->addTenant('tenant_b_id', 2);

        Landlord::shouldReceive('applyTenantScopes');

        $model = new ModelStubWithBelongsToOneTenant();

        $landlord->newModel($model);

        $this->assertEquals(1, $model->tenant_a_id);

        $this->assertNull($model->tenant_b_id);
    }

    public function testNewModelRelatedToManyTenants()
    {
        $landlord = new TenantManager();

        $landlord->addTenant('tenant_a_id', 1);
        $landlord->addTenant('tenant_b_id', 2);

        Landlord::shouldReceive('applyTenantScopes');

        $tenant = \Mockery::mock(TenantA::class);
        $tenant->shouldReceive('syncWithoutDetaching')->with([1, 2]);

        $mock = \Mockery::mock(ModelStubWithBelongsToManyTenants::class);
        $mock->shouldReceive('getTenantModel')->andReturn();
        $mock->shouldReceive('tenants')->andReturn($tenant);
        $mock->makePartial();

        app()->instance(ModelStubWithBelongsToManyTenants::class, $mock);
        $model = app()->make(ModelStubWithBelongsToManyTenants::class);

        $landlord->newModelRelatedToManyTenants($model);
    }
}

class ModelStubWithBelongsToManyTenants extends Model
{
    use BelongsToTenants;

    public $tenantColumns = ['tenant_a_id'];
    public $belongsToTenantType = TenantManager::BELONGS_TO_TENANT_TYPE_TO_MANY;
}

class ModelStubWithBelongsToOneTenant extends Model
{
    use BelongsToTenants;

    public $tenantColumns = ['tenant_a_id'];
    public $belongsToTenantType = TenantManager::BELONGS_TO_TENANT_TYPE_TO_ONE;
}

class TenantA extends Model
{
    //
}

class TenantB extends Model
{
    //
}
