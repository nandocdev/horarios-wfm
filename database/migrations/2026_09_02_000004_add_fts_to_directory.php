<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent;');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm;');

        // FTS para servicios (nombre + cargo/rol + puerta)
        DB::statement("
            ALTER TABLE directory_services
            ADD COLUMN IF NOT EXISTS tsv_service tsvector
            GENERATED ALWAYS AS (
                to_tsvector('spanish',
                    coalesce(name,'') || ' ' ||
                    coalesce(contact_role,'') || ' ' ||
                    coalesce(door_id,'') || ' ' ||
                    coalesce(office_number,'')
                )
            ) STORED
        ");

        DB::statement('CREATE INDEX IF NOT EXISTS idx_services_fts ON directory_services USING GIN(tsv_service);');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_services_trgm ON directory_services USING GIN(name gin_trgm_ops);');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_phones_trgm ON directory_phones USING GIN(number gin_trgm_ops);');

        // FTS para reglas (qué NO se hace + derivaciones)
        DB::statement("
            ALTER TABLE directory_service_rules
            ADD COLUMN IF NOT EXISTS tsv_rule tsvector
            GENERATED ALWAYS AS (
                to_tsvector('spanish',
                    coalesce(service_name,'') || ' ' ||
                    coalesce(requirements,'') || ' ' ||
                    coalesce(exclusions,'') || ' ' ||
                    coalesce(external_referral,'') || ' ' ||
                    coalesce(referral_specialists,'')
                )
            ) STORED
        ");
        DB::statement('CREATE INDEX IF NOT EXISTS idx_rules_fts ON directory_service_rules USING GIN(tsv_rule);');

        // FTS para trámites/faqs
        DB::statement("
            ALTER TABLE directory_faqs
            ADD COLUMN IF NOT EXISTS tsv_faq tsvector
            GENERATED ALWAYS AS (
                to_tsvector('spanish',
                    coalesce(subject,'') || ' ' ||
                    coalesce(procedure_steps,'') || ' ' ||
                    coalesce(required_documents,'')
                )
            ) STORED
        ");
        DB::statement('CREATE INDEX IF NOT EXISTS idx_faqs_fts ON directory_faqs USING GIN(tsv_faq);');

        // B-Tree para navegación jerárquica
        DB::statement('CREATE INDEX IF NOT EXISTS idx_units_building ON directory_units(building_id);');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_services_unit ON directory_services(unit_id);');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_faqs_fts;');
        DB::statement('DROP INDEX IF EXISTS idx_rules_fts;');
        DB::statement('DROP INDEX IF EXISTS idx_phones_trgm;');
        DB::statement('DROP INDEX IF EXISTS idx_services_trgm;');
        DB::statement('DROP INDEX IF EXISTS idx_services_fts;');
        DB::statement('ALTER TABLE directory_services DROP COLUMN IF EXISTS tsv_service;');
        DB::statement('ALTER TABLE directory_service_rules DROP COLUMN IF EXISTS tsv_rule;');
        DB::statement('ALTER TABLE directory_faqs DROP COLUMN IF EXISTS tsv_faq;');
    }
};
