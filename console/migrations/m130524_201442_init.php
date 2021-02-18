<?php

use yii\db\Migration;

class m130524_201442_init extends Migration
{
    public function up()
    {
        $citext = Yii::$app->db->createCommand("
          SELECT *
          FROM pg_available_extensions
          WHERE (installed_version IS NOT NULL)
          AND \"name\" = 'citext';
        ")->queryOne();
        if (!is_array($citext)) {
            echo "You need install citext extension for continue the migrations setup.\n";
            echo "Run command << CREATE EXTENSION IF NOT EXISTS \"citext\" WITH SCHEMA public; >> from user = postgres (superuser) in SQL command line\n";
            return false;
        }

        $citext = Yii::$app->db->createCommand("
          SELECT *
          FROM pg_available_extensions
          WHERE (installed_version IS NOT NULL)
          AND \"name\" = 'uuid-ossp';
        ")->queryOne();
        if (!is_array($citext)) {
            echo "You need install uuid-ossp extension for continue the migrations setup.\n";
            echo "Run command << CREATE EXTENSION \"uuid-ossp\" WITH SCHEMA public; >> from user = postgres (superuser) in SQL command line\n";
            return false;
        }

        return true;
    }

    public function down()
    {

    }
}
