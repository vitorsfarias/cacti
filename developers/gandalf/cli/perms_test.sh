echo -----------------------
echo  list options
echo -----------------------
php -q perms_list.php --help
#php -q perms_list.php --list-groups
php -q perms_list.php --list-users
php -q perms_list.php --list-trees
php -q perms_list.php --list-realms
# user "admin" should always exist
php -q perms_list.php --list-realms --user-id=1
php -q perms_list.php --list-realms --realm-id=7
# plugin management realm should always exist
php -q perms_list.php --list-realms --user-id=1 --realm-id=101
php -q perms_list.php --list-perms
php -q perms_list.php --list-perms --user-id=1
php -q perms_list.php --list-perms --item-type=graph
# make sure, that a graph exists: 54
php -q perms_list.php --list-perms --item-id=54
php -q perms_list.php --list-perms --user-id=1 --item-type=graph
echo This will throw errors
php -q perms_list.php --list-realms --user-id=bar
php -q perms_list.php --list-realms --realm-id=foo

clear
echo -----------------------
echo add and remove perms
echo -----------------------
php -q perms_create.php --help
# get a valid graph id and replace here: 54
php -q perms_create.php --item-type=graph --user-id=3,1 --item-id=54
php -q perms_list.php --list-perms --item-type=graph
php -q perms_delete.php --item-type=graph --user-id=3,1 --item-id=54
php -q perms_list.php --list-perms --item-type=graph

php -q perms_create.php --item-type=device --user-id=3,1 --item-id=1
php -q perms_list.php --list-perms --item-type=device
php -q perms_delete.php --item-type=device --user-id=3,1 --item-id=1
php -q perms_list.php --list-perms --item-type=device

php -q perms_create.php --item-type=graph_template --user-id=3,1 --item-id=2
php -q perms_list.php --list-perms --item-type=graph_template
php -q perms_delete.php --item-type=graph_template --user-id=3,1 --item-id=2
php -q perms_list.php --list-perms --item-type=graph_template
