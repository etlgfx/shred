SHRED Framework
===
This is yet another PHP web framework. The goal of this project is to create a
Framework that is light weight, clean, and gets the hell out of your way. 


Status
---
SHRED is definitely not yet ready for wide spread use. There is little to no
documentation, and quite a bit more work has to be done. I'd call the current
version 0.2a.

It has been my personal pet project for the past couple years, and has gone
through many refactors along the way. I will tighten things up one of these
days.


Usage
---
For now I'm not suggesting anyone use this, but you can poke around if you
like.

Simply run:

	./shell

If you have PHPUnit installed try:

	./shell unit-test


Features
---
Here are a list of handy features roughly in order of creation:

* easy configuration
<pre>
	Config::set('key.subkey.subkey', 'value');
	Config::get('key.subkey.subkey'); // => 'value'
	Config::get('key); // => array('subkey' => array('subkey' => 'value'))
</pre>

* flexible routing including specific REST goodies
<pre>
	Config::set('router.routes', array(
		'path/edit/[id:num]' => array(
			'controller' => 'object',
			'action' => 'edit',
		),
		'method:delete;url:object/[id:num]' => array(
			'controller' => 'object',
			'action' => 'delete',
		)
	);

	class Controller_Object extends \Shred\Controller_Abstract {
		public function edit() {
			$this->request->id;
		}

		public function delete() {
			$this->request->id;
		}
	}
</pre>

* Easy db querying using `\Shred\QBuilder` (and shorthand alias class `Q`) which is basically syntactic sugar around PDO
<pre>
	Q::select(/* column list */)->from('table')->join('jointable')->on('condition_lhs', 'condition_rhs')->where('column', 'value')->where('column', '>', 'operatorcompared')->order('column', 'asc')->limit(1)->execute();
</pre>

* Non-Fancy new ORM `\Shred\Model\_Abstract`; basic relational mapping support and CRUD features

* easy querying directly from ORM
<pre>
	Model_Name::where('column', 'value')->findOne()
</pre>
