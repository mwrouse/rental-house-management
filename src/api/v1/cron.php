<?php
/**
 * This file should be ran by cron daily
 */
require_once('framework/include.php');
require_once('permissions.php');



Scheduler::RunScheduledItems();