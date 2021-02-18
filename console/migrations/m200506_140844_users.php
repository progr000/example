<?php

use yii\db\Migration;

/**
 * Class m200506_140844_users
 */
class m200506_140844_users extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $schema   = isset(Yii::$app->components['db']['schemaMap']['pgsql']['defaultSchema'])
            ? Yii::$app->components['db']['schemaMap']['pgsql']['defaultSchema']
            : 'public';

        $tablePrefix = isset(Yii::$app->components['db']['tablePrefix'])
            ? Yii::$app->components['db']['tablePrefix']
            : '';

        $userName = isset(Yii::$app->components['db']['username'])
            ? Yii::$app->components['db']['username']
            : 'username';

        $this->db->pdo->exec("
            SET search_path TO {$schema}, public;

            CREATE SEQUENCE {$schema}.{$tablePrefix}users_user_id_seq
                INCREMENT 1
                START 1
                MINVALUE 1
                MAXVALUE 9223372036854775807
                CACHE 1;

            ALTER SEQUENCE {$schema}.{$tablePrefix}users_user_id_seq
                OWNER TO {$userName};

            CREATE TABLE {$schema}.{$tablePrefix}users
            (
                user_id BIGINT PRIMARY KEY NOT NULL DEFAULT nextval('{$tablePrefix}users_user_id_seq'::regclass),
                user_created TIMESTAMP WITHOUT TIME ZONE NOT NULL,
                user_updated TIMESTAMP WITHOUT TIME ZONE NOT NULL,

                password_hash CHARACTER VARYING(255) NOT NULL,
                password_reset_token CHARACTER VARYING(255), --unique
                verification_token CHARACTER VARYING(255), --unique
                auth_key CHARACTER VARYING(32) NOT NULL,

                user_first_name PUBLIC.CITEXT NOT NULL,
                user_middle_name PUBLIC.CITEXT NOT NULL DEFAULT '',
                user_last_name PUBLIC.CITEXT NOT NULL,
                user_full_name PUBLIC.CITEXT NOT NULL,

                user_email PUBLIC.CITEXT NOT NULL, --unique
                user_phone CHARACTER VARYING(50),
                user_last_pay TIMESTAMP WITHOUT TIME ZONE,

                user_token CHARACTER VARYING(32) NOT NULL, --unique
                user_hash CHARACTER VARYING(128) DEFAULT NULL::character varying, --unique
                user_status SMALLINT NOT NULL DEFAULT 0,
                user_type SMALLINT NOT NULL DEFAULT 0,

                operator_user_id BIGINT,
                operator_notice PUBLIC.CITEXT,
                methodist_user_id BIGINT,
                methodist_notice PUBLIC.CITEXT,
                teacher_user_id BIGINT,
                teacher_notice PUBLIC.CITEXT,

                user_balance NUMERIC(11,2) NOT NULL DEFAULT 0.00, -- Ballance
                user_last_ip BIGINT NOT NULL DEFAULT '0'::bigint,

                FOREIGN KEY (operator_user_id) REFERENCES {$schema}.{$tablePrefix}users (user_id) MATCH SIMPLE
                    ON UPDATE CASCADE
                    ON DELETE SET NULL,
                FOREIGN KEY (methodist_user_id) REFERENCES {$schema}.{$tablePrefix}users (user_id) MATCH SIMPLE
                    ON UPDATE CASCADE
                    ON DELETE SET NULL,
                FOREIGN KEY (teacher_user_id) REFERENCES {$schema}.{$tablePrefix}users (user_id) MATCH SIMPLE
                    ON UPDATE CASCADE
                    ON DELETE SET NULL
            ) WITH (
                OIDS = FALSE
            )
            TABLESPACE pg_default;

            CREATE UNIQUE INDEX idx_users_password_reset_token
                ON {$schema}.{$tablePrefix}users USING BTREE (password_reset_token);

            CREATE UNIQUE INDEX idx_users_verification_token
                ON {$schema}.{$tablePrefix}users USING BTREE (verification_token);

            CREATE UNIQUE INDEX idx_users_user_email
                ON {$schema}.{$tablePrefix}users USING BTREE (user_email);

            CREATE UNIQUE INDEX idx_users_user_token
                ON {$schema}.{$tablePrefix}users USING BTREE (user_token);

            CREATE UNIQUE INDEX idx_users_user_hash
                ON {$schema}.{$tablePrefix}users USING BTREE (user_hash);


            ALTER TABLE {$schema}.{$tablePrefix}users
                OWNER to {$userName};
        ");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200506_140844_users cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200506_140844_users cannot be reverted.\n";

        return false;
    }
    */
}
