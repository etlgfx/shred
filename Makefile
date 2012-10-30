autoload: 
	grep --exclude=Autoload.class.php -or "^\(<?\(php\)\? \)\?\(abstract class\|class\|interface\) \w\+" libs | cut -c 6- | sed 's/\(.*\):\(<?\(php\)\? \)\?\(abstract class\|class\|interface\) \(.*\)/\5=\1/' | php scripts/generator.php > /tmp/config.ini
	cp /tmp/config.ini autoload.ini

test: autoload output-dirs
	phpunit -v --debug --bootstrap tests/include.php --coverage-html out/reports tests/

output-dirs:
	mkdir -p out/reports
