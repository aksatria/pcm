@echo off
echo === SETUP WBS SYSTEM - FIXED VERSION ===

echo 1. Deleting problematic migration files...
del database\migrations\2025_12_11_042311_create_wbs_tables.php 2>nul
echo    Deleted.

echo 2. Cleaning database...
sqlite3 database/database.sqlite "DROP TABLE IF EXISTS wbs_budget_sources;" 2>nul
sqlite3 database/database.sqlite "DROP TABLE IF EXISTS wbs_items;" 2>nul
sqlite3 database/database.sqlite "DROP TABLE IF EXISTS work_breakdown_structures;" 2>nul
sqlite3 database/database.sqlite "DELETE FROM migrations WHERE migration LIKE '%wbs%' OR migration LIKE '%work_breakdown%';" 2>nul
echo    Database cleaned.

echo 3. Creating new migration...
php artisan make:migration create_work_breakdown_system_tables
echo    Migration created.

echo 4. Please edit the migration file manually now.
echo    File: database\migrations\[timestamp]_create_work_breakdown_system_tables.php
echo    Copy the correct migration code from the instructions.
pause

echo 5. Running migration...
php artisan migrate
echo    Migration completed.

echo 6. Creating seeder...
php artisan make:seeder WBSSeeder
echo    Seeder created.

echo 7. Please edit the seeder file manually now.
echo    File: database\seeders\WBSSeeder.php
echo    Copy the correct seeder code from the instructions.
pause

echo 8. Running seeder...
php artisan db:seed --class=WBSSeeder

echo 9. Clearing cache...
php artisan optimize:clear

echo === WBS SYSTEM SETUP COMPLETED ===
echo You can now access WBS at: /dev/projects/{projectId}/wbs
pause