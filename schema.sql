create table users
(
    id int auto_increment,
    name varchar(64) not null,
    email varchar(256) not null,
    created DATETIME not null,
    deleted DATETIME null,
    notes TEXT null,
    constraint users_pk
        primary key (id)
);

create unique index users_email_uindex
    on users (email);

create unique index users_name_uindex
    on users (name);