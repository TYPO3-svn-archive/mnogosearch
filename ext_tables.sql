#
# Table structure for table 'tx_mnogosearch_indexconfig'
#
CREATE TABLE tx_mnogosearch_indexconfig (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	tx_mnogosearch_type int(11) DEFAULT '0' NOT NULL,
	tx_mnogosearch_url varchar(255) DEFAULT '' NOT NULL,
	tx_mnogosearch_method int(11) DEFAULT '-1' NOT NULL,
	tx_mnogosearch_subsection int(11) DEFAULT '-1' NOT NULL,
	tx_mnogosearch_cmptype int(11) DEFAULT '-1' NOT NULL,
	tx_mnogosearch_cmpoptions int(11) DEFAULT '0' NOT NULL,
	tx_mnogosearch_period int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY sorting (sorting)
);

CREATE TABLE tx_mnogosearch_urllog (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	tx_mnogosearch_url varchar(255) DEFAULT '' NOT NULL,
	tx_mnogosearch_pid int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY tx_mnogosearch_pid (tx_mnogosearch_pid)
);
