<?php

namespace hampton\accessibility\migrations;

use craft\db\Connection;
use craft\db\Migration;
use craft\records\Site;

class Install extends Migration {

    public function safeUp(): bool {
        $table = "accessibility_scans";

        if (!$this->db->tableExists($table)) {
            $this->createTable($table, [
                'id' => $this->primaryKey(),
                'scanId' => $this->bigInteger()->defaultValue(20),
                'entryId' => $this->integer()->notNull(),
                'severity' => $this->integer()->notNull(),
                'issue' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        return true;
    }

    public function safeDown(): bool {
        $this->dropTableIfExists('accessibility_scans');
        return true;
    }
}
