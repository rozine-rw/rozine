<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Complaints (BRS v2.0 CMP-R03): every complaint gets a reference number, an acknowledgement within
 * 4 hours and a resolution within 48 hours. A complaint is opened in-app by an Investor or a Business
 * Party and carries both clocks from the moment it was opened. Its conversation is a list of
 * messages from the customer and from Rozine staff; the first staff message acknowledges it and a
 * staff message marked as the resolution closes it. Messages are personal data, so their text is held
 * encrypted. Neither a complaint nor a message is ever edited or removed once written.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('reference', 20)->unique();
            $table->foreignUlid('party_id')->constrained('parties')->restrictOnDelete();
            $table->string('role', 16);
            $table->string('channel', 16);
            $table->foreignId('opened_by_user_id')->constrained('users')->restrictOnDelete();
            $table->ulid('operation_id')->unique();
            $table->timestampTz('acknowledge_by', 6);
            $table->timestampTz('resolve_by', 6);
            $table->timestampTz('created_at', 6);
            $table->index(['party_id', 'role', 'created_at']);
        });
        Schema::create('complaint_messages', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('complaint_id')->constrained('complaints')->restrictOnDelete();
            $table->string('author', 16);
            $table->foreignId('author_user_id')->constrained('users')->restrictOnDelete();
            $table->boolean('resolves');
            $table->text('body');
            $table->ulid('operation_id')->unique();
            $table->timestampTz('created_at', 6);
            $table->index(['complaint_id', 'created_at']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE complaints ADD CONSTRAINT complaint_role_known CHECK (role IN ('investor', 'business'));
            ALTER TABLE complaints ADD CONSTRAINT complaint_channel_known CHECK (channel IN ('in_app'));
            ALTER TABLE complaints ADD CONSTRAINT complaint_clocks_ordered CHECK (created_at < acknowledge_by AND acknowledge_by < resolve_by);
            ALTER TABLE complaint_messages ADD CONSTRAINT complaint_message_author_known CHECK (author IN ('customer', 'staff'));
            ALTER TABLE complaint_messages ADD CONSTRAINT complaint_message_staff_resolves CHECK (NOT resolves OR author = 'staff');
            CREATE UNIQUE INDEX complaint_messages_one_resolution ON complaint_messages (complaint_id) WHERE resolves;
            CREATE OR REPLACE FUNCTION reject_complaint_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Complaints and their messages are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER complaints_immutable BEFORE UPDATE OR DELETE ON complaints
            FOR EACH ROW EXECUTE FUNCTION reject_complaint_mutation();
            CREATE TRIGGER complaint_messages_immutable BEFORE UPDATE OR DELETE ON complaint_messages
            FOR EACH ROW EXECUTE FUNCTION reject_complaint_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_messages');
        Schema::dropIfExists('complaints');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_complaint_mutation()');
    }
};
