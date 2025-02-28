CREATE TABLE discount (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  userid bigint(20) UNSIGNED NOT NULL,
  code varchar(20) NOT NULL,
  amount tinyint(1) UNSIGNED NOT NULL,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
)
ENGINE = INNODB,
CHARACTER SET utf8mb4,
COLLATE utf8mb4_general_ci;

ALTER TABLE discount
ADD UNIQUE INDEX code (code);

ALTER TABLE discount
ADD INDEX userid (userid);