<?php

use \pew\db\Database;

class DatabaseTest extends PHPUnit_Framework_TestCase
{
    public $config = [
        'engine' => 'sqlite',
        'file' => 'assets/test.sqlite',
    ];

    public $pdo;

    public function __construct()
    {
        parent::__construct();

        $pdo = new PDO('sqlite::memory:');

        $artists = [
            [
                'name' => 'The Smashing Pumpkins',
                'albums' => [
                    [
                        'name' => 'Siamese Dream',
                        'year' => 1993,
                        'songs' => [
                            'Hummer',
                            'Disarm',
                            'Soma',
                            'Mayonaise'
                        ]
                    ],
                    [
                        'name' => 'Pisces Iscariot',
                        'year' => 1994,
                        'songs' => [
                            'Plume',
                            'Whir',
                            'Landslide'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Placebo',
                'albums' => [
                    [
                        'name' => 'Without You I\'m Nothing',
                        'year' => 1998,
                        'songs' => [
                            'Pure Morning',
                            'Brick Shithouse',
                            'You Don\'t Care About Us',
                            'Allergic (to Thoughts of Mother Earth)',
                            'Every You Every Me',
                        ]
                    ],
                    [
                        'name' => 'Black Market Music',
                        'year' => 2000,
                        'songs' => [
                            'Taste in Men',
                            'Special K',
                            'Spice & Malice',
                            'Black-Eyed',
                            'Peeping Tom',
                        ]
                    ],
                    [
                        'name' => 'Sleeping With Ghosts',
                        'year' => 2002,
                        'songs' => [
                            'English Summer Rain',
                            'This Picture',
                            'Special Needs',
                            'Second Sight',
                            'Centrefolds',
                        ]
                    ]
                ]
            ],
            [
                'name' => 'The Who',
                'albums' => [
                    [
                        'name' => 'Who\'s Next',
                        'year' => 1971,
                        'songs' => [
                            'Baba O\'Riley',
                            'My Wife',
                            'Going Mobile',
                            'Behind Blue Eyes'
                        ]
                    ]
                ]
            ]
        ];

        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $pdo->exec("CREATE TABLE artists (id INTEGER PRIMARY KEY, name TEXT)");
        $pdo->exec("CREATE TABLE albums  (id INTEGER PRIMARY KEY, name TEXT, year INTEGER, artist_id INTEGER)");
        $pdo->exec("CREATE TABLE songs   (id INTEGER PRIMARY KEY, name TEXT, track_number INTEGER, album_id INTEGER)");
        
        $insert_artist = $pdo->prepare("INSERT INTO artists (name) VALUES (?)");
        $insert_album = $pdo->prepare("INSERT INTO albums (name, year, artist_id) VALUES (?, ?, ?)");
        $insert_song = $pdo->prepare("INSERT INTO songs (name, track_number, album_id) VALUES (?, ?, ?)");

        $pdo->beginTransaction();

        foreach ($artists as $artist) {
            $insert_artist->execute([$artist['name']]);
            $artist_id = $pdo->lastInsertId();
            
            foreach ($artist['albums'] as $album) {
                $insert_album->execute([$album['name'], $album['year'], $artist_id]);
                $album_id = $pdo->lastInsertId();

                foreach ($album['songs'] as $track_number => $song) {
                    $insert_song->execute([$song, $track_number + 1, $album_id]);
                }
            }
        }

        $this->pdo = $pdo;
    }

    public function __destruct()
    {
        if (file_exists('assets/db.sqlite')) {
            unlink('assets/db.sqlite');
        }
    }

    public function testBadConfiguration()
    {
        try {
            $db = new Database(['engne' => 'sqlite', 'file' => 'dummy.db']);
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('InvalidArgumentException', get_class($e));
        }
    }

    public function testBadConnection()
    {
        try {
            $db = new Database([
                'engine' => 'mysql',
                'user' => 'dummy',
                'pass' => 'dummy',
                'host' => 'dummy',
                'name' => 'dummy'
            ]);
        } catch (\PDOException $e) {
            $this->assertEquals('PDOException', get_class($e));
        }
    }

    public function testSqliteConnection()
    {
        $db = new Database([
                'engine' => 'sqlite',
                'file' => 'testdb.db'
            ]);

        $this->assertEquals('PDO', get_class($db->pdo()));
        $this->assertTrue(file_exists('testdb.db'));
        $db->disconnect();

        unlink('testdb.db');
    }

    public function testAttachPdoConnection()
    {
        $db = new Database;
        $db->pdo($this->pdo);

        $this->assertEquals(3, count($db->select('artists')));

    }

    public function testTableMetadata()
    {
        $db = new Database;
        $db->pdo($this->pdo);

        $this->assertTrue($db->table_exists('albums'));
        //$this->assertFalse($db->table_exists('users'));

        $this->assertEquals('id', $db->get_pk('artists'));
        $this->assertEquals(['id'], $db->get_pk('songs', true));

        $this->assertEquals(['id', 'name'], $db->get_cols('artists'));
    }

    public function testSelectSingleAndCellWithoutFrom()
    {
        $db = new Database($this->pdo);

        $artists = $db->select('artists');
        $first_album = $db->single('albums');
        $song_name = $db->cell('name', 'songs');

        $this->assertEquals(3, count($artists));
        $this->assertEquals(4, count($first_album));
        $this->assertTrue(array_key_exists('artist_id', $first_album));
        $this->assertTrue(is_string($song_name));
        $this->assertEquals('Hummer', $song_name);
    }

    public function testSelectSingleAndCellWithFrom()
    {
        $db = new Database($this->pdo);

        $artists = $db->from('artists')->select();
        $first_album = $db->from('albums')->single();
        $song_name = $db->from('songs')->cell('name');

        $this->assertEquals(3, count($artists));
        $this->assertEquals(4, count($first_album));
        $this->assertTrue(array_key_exists('artist_id', $first_album));
        $this->assertTrue(is_string($song_name));
        $this->assertEquals('Hummer', $song_name);
    }

    public function testWhere()
    {
        $db = new Database($this->pdo);
        
        $placebo = $db->where(['name' => 'Placebo'])->select('artists');
        
        $this->assertEquals(1, count($placebo));
        $this->assertEquals('Placebo', $placebo[0]['name']);

        $result = $db->where(['name' => ['LIKE', 'The %']])->select('artists');

        $this->assertEquals(2, count($result));
        $this->assertTrue(array_key_exists('name', $result[0]));
        $this->assertTrue(array_key_exists('name', $result[1]));

        $db->where(['year' => ['BETWEEN', 1994, 1995]]);
        $pisces = $db->select('albums', 'name');

        $this->assertEquals(1, count($pisces));
        $this->assertEquals('Pisces Iscariot', $pisces[0]['name']);
        $this->assertFalse(isSet($pisces[0]['id']));
    }

    public function testLimitAndOrderBy()
    {
        $db = new Database($this->pdo);
        
        $result = $db->order_by('year ASC')->limit(5)->select('albums');
        $this->assertEquals(5, count($result));

        $result = $db->order_by('year')->limit(1)->select('albums');
        $this->assertEquals(1, count($result));
        $this->assertEquals('Who\'s Next', $result[0]['name']);
        $this->assertEquals(1971, $result[0]['year']);

        $result = $db->order_by('year')->limit('1, 1')->select('albums');
        $this->assertEquals(1, count($result));
        $this->assertEquals('Siamese Dream', $result[0]['name']);
        $this->assertEquals(1993, $result[0]['year']);

        $result = $db->order_by('year DESC')->limit('1')->select('albums');
        $this->assertEquals(1, count($result));
        $this->assertEquals('Sleeping With Ghosts', $result[0]['name']);
        $this->assertEquals(2002, $result[0]['year']);
    }

    public function testGroupAndHaving()
    {
        $db = new Database($this->pdo);
        
        $result = $db
            ->group_by('artist_id')
            ->fields('COUNT(*) as album_count, artists.name')
            ->where(['artists.id = albums.artist_id'])
            ->order_by('artists.name')
            ->select('albums, artists');

        $this->assertEquals(3, count($result));
        $this->assertEquals(3, $result[0]['album_count']);
        $this->assertEquals(2, $result[1]['album_count']);
        $this->assertEquals(1, $result[2]['album_count']);
    }

    public function testInsert()
    {
        $db = new Database($this->pdo);
        
        $lastInsertId = $db->values(['name'=> 'Mice Parade'])->insert('artists');
        $this->assertEquals(4, $lastInsertId);

        $mice_parade = $db->where(['name' => 'Mice Parade'])->single('artists');
        $this->assertEquals('Mice Parade', $mice_parade['name']);

        $lastInsertId = $db->values(['name'=> 'Elbow'])->into('artists')->insert();
        $this->assertEquals(5, $lastInsertId);

        $elbow = $db->where(['name' => 'Elbow'])->single('artists');
        $this->assertEquals('Elbow', $elbow['name']);
    }

    public function testUpdate()
    {
        $db = new Database($this->pdo);
        
        $mayonaiseId = $db->where(['name'=> 'Mayonaise'])->cell('id', 'songs');
        $this->assertTrue(is_numeric($mayonaiseId));

        $affectedRows = $db->set(['name' => 'Mayonaise (remix)'])->where(['id' => $mayonaiseId])->update('songs');
        $this->assertEquals(1, $affectedRows);

        $mayonaise_remix = $db->where(['id' => $mayonaiseId])->single('songs');
        $this->assertEquals('Mayonaise (remix)', $mayonaise_remix['name']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage No table provided for method PewDatabase::update()
     */
    public function testUpdateWithoutSpecifyingTable()
    {
        $db = new Database($this->pdo);
        $db->set(['name' => 'Stabbing Westward'])->where(['id' => 5])->update();
    }

    public function testDelete()
    {
        $db = new Database($this->pdo);

        $mayonaiseId = $db->where(['name'=> 'Mayonaise'])->cell('id', 'songs');
        $affectedRows = $db->where(['id' => $mayonaiseId])->delete('songs');
        $this->assertEquals(1, $affectedRows);
        $this->assertFalse($db->where(['id' => $mayonaiseId])->single('songs'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage No table provided for method PewDatabase::delete()
     */
    public function testDeleteWithoutSpecifyingTable()
    {
        $db = new Database($this->pdo);
        $db->delete();
    }
}
