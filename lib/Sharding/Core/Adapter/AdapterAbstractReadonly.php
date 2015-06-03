<?php 

namespace Sharding\Core\Adapter;

use Sharding\Core\Adapter\AdapterAbstract;

abstract class AdapterAbstractReadonly extends AdapterAbstract 
{
	public final function save()
	{
		return;
	}
	
	public final function delete()
	{
		return;
	}
	
	public final function update()
	{
		return;
	}
	
	public final function createShardTable($tblName, $data)
	{
		return;
	}
	
	public final function createTableBySample($tblName)
	{
		return;
	}
	
	public final function createShardMap($tblName, $data)
	{
		return;
	}
}