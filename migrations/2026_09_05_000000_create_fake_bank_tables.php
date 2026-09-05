<?php

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateFakeBankTables extends Migration
{
    public function up(): void
    {
        // 1. fb_users
        Schema::create('fb_users', function (Blueprint $table) {
            $table->bigIncrements('us_id');
            $table->uuid('us_uuid')->unique();
            $table->string('us_name', 150);
            $table->string('us_cpf', 11)->unique();
            $table->string('us_password', 255);
            $table->string('us_status', 20)->default('pending_creation');
            $table->timestamp('us_created_at');
            $table->timestamp('us_updated_at');
            $table->timestamp('us_deleted_at')->nullable();
        });

        // 2. fb_accounts
        Schema::create('fb_accounts', function (Blueprint $table) {
            $table->bigIncrements('ac_id');
            $table->uuid('ac_uuid')->unique();
            $table->unsignedBigInteger('us_id');
            $table->string('ac_number', 11)->unique();
            $table->decimal('ac_balance', 15, 2)->default(0.00);
            $table->string('ac_status', 20)->default('active');
            $table->timestamp('ac_created_at');
            $table->timestamp('ac_updated_at');
            $table->timestamp('ac_deleted_at')->nullable();

            $table->foreign('us_id')->references('us_id')->on('fb_users')->onDelete('cascade');
        });

        // 3. fb_pix_keys
        Schema::create('fb_pix_keys', function (Blueprint $table) {
            $table->bigIncrements('pk_id');
            $table->uuid('pk_uuid')->unique();
            $table->unsignedBigInteger('us_id');
            $table->unsignedBigInteger('ac_id');
            $table->string('pk_type', 10);
            $table->string('pk_key', 100)->unique();
            $table->timestamp('pk_created_at');
            $table->timestamp('pk_updated_at');
            $table->timestamp('pk_deleted_at')->nullable();

            $table->foreign('us_id')->references('us_id')->on('fb_users')->onDelete('cascade');
            $table->foreign('ac_id')->references('ac_id')->on('fb_accounts')->onDelete('cascade');
        });

        // 4. fb_transactions
        Schema::create('fb_transactions', function (Blueprint $table) {
            $table->bigIncrements('tr_id');
            $table->uuid('tr_uuid')->unique();
            $table->unsignedBigInteger('ac_id_origin')->nullable();
            $table->unsignedBigInteger('ac_id_destination');
            $table->string('tr_type', 20);
            $table->decimal('tr_amount', 15, 2);
            $table->string('tr_status', 20)->default('pending');
            $table->json('tr_payload')->nullable();
            $table->timestamp('tr_created_at');
            $table->timestamp('tr_updated_at');
            $table->timestamp('tr_deleted_at')->nullable();

            $table->foreign('ac_id_origin')->references('ac_id')->on('fb_accounts')->onDelete('set null');
            $table->foreign('ac_id_destination')->references('ac_id')->on('fb_accounts')->onDelete('cascade');
        });

        // 5. fb_logs_login
        Schema::create('fb_logs_login', function (Blueprint $table) {
            $table->bigIncrements('ll_id');
            $table->uuid('ll_uuid')->unique();
            $table->unsignedBigInteger('us_id')->nullable();
            $table->string('ll_credential', 100);
            $table->boolean('ll_login_successfully');
            $table->timestamp('ll_created_at');

            $table->foreign('us_id')->references('us_id')->on('fb_users')->onDelete('set null');
        });

        // 6. fb_log_error
        Schema::create('fb_log_error', function (Blueprint $table) {
            $table->bigIncrements('er_id');
            $table->uuid('er_uuid')->unique();
            $table->string('er_page', 255);
            $table->string('er_file', 255);
            $table->string('er_method', 100);
            $table->integer('er_line');
            $table->text('er_message');
            $table->json('er_data');
            $table->json('er_data_sanitized')->nullable();
            $table->timestamp('er_date_error');
        });

        // 7. fb_logs_action
        Schema::create('fb_logs_action', function (Blueprint $table) {
            $table->bigIncrements('la_id');
            $table->uuid('la_uuid')->unique();
            $table->unsignedBigInteger('us_id')->nullable();
            $table->unsignedBigInteger('er_id')->nullable();
            $table->string('la_action', 100);
            $table->json('la_action_metadata');
            $table->boolean('la_error')->default(false);
            $table->timestamp('la_created_at');

            $table->foreign('us_id')->references('us_id')->on('fb_users')->onDelete('set null');
            $table->foreign('er_id')->references('er_id')->on('fb_log_error')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fb_logs_action');
        Schema::dropIfExists('fb_log_error');
        Schema::dropIfExists('fb_logs_login');
        Schema::dropIfExists('fb_transactions');
        Schema::dropIfExists('fb_pix_keys');
        Schema::dropIfExists('fb_accounts');
        Schema::dropIfExists('fb_users');
    }
}
