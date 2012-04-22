autoload: 
	grep --exclude=Autoload.class.php -or "^\(abstract class\|class\|interface\) \w\+" libs | cut -c 6- | sed 's/\(.*\):\(abstract class\|class\|interface\) \(.*\)/\3=\1/' | php scripts/generator.php > /tmp/config.ini
	cp /tmp/config.ini autoload.ini

test: autoload output-dirs
	phpunit --bootstrap tests/unit/bootstrap.php --coverage-html out/reports tests/

output-dirs:
	mkdir -p out/reports
