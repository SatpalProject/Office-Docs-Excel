<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPageSettingsToDocumentsTable extends Migration
{
    public function up()
    {
        $fields = [
            'header_content' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'content',
            ],
            'footer_content' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'header_content',
            ],
            'page_settings' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'footer_content',
            ],
        ];

        $this->forge->addColumn('documents', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('documents', ['header_content', 'footer_content', 'page_settings']);
    }
}
