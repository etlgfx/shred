test: output-dirs
	phpunit --colors --bootstrap tests/include.php --coverage-html out/reports tests/

output-dirs:
	mkdir -p out/reports
