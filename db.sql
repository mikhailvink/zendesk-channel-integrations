create schema zendesk_integrations collate latin1_swedish_ci
;

create table zendesk_integrations_logs_app
(
	ID int auto_increment,
	CREATEDON datetime not null,
	IP varchar(255) not null,
	URL varchar(255) not null,
	MESSAGE longtext not null,
	TYPE varchar(255) not null,
	SUBDOMAIN varchar(255) not null,
	constraint ID
		unique (ID)
)
charset=utf8
;

create index SUBDOMAIN
	on zendesk_integrations_logs_app (SUBDOMAIN)
;

create index TYPE
	on zendesk_integrations_logs_app (TYPE)
;

alter table zendesk_integrations_logs_app
	add primary key (ID)
;

create table zendesk_integrations_logs_callback
(
	ID int auto_increment,
	CREATEDON datetime not null,
	IP varchar(255) not null,
	URL varchar(255) not null,
	CALLBACK longtext not null,
	TYPE varchar(255) not null,
	SUBDOMAIN varchar(255) not null,
	EVENT_TYPE_ID varchar(255) not null,
	ERROR_FLAG tinyint(1) not null,
	constraint ID
		unique (ID)
)
charset=utf8
;

create index EVENT_TYPE_ID
	on zendesk_integrations_logs_callback (EVENT_TYPE_ID)
;

create index SUBDOMAIN
	on zendesk_integrations_logs_callback (SUBDOMAIN)
;

create index TYPE
	on zendesk_integrations_logs_callback (TYPE)
;

alter table zendesk_integrations_logs_callback
	add primary key (ID)
;

