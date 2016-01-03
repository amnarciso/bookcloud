<?php
namespace Application\Model;

use Zend\Db\Sql\Sql;

class Books
{
    protected $sm;
    
    public function __construct($sm)
    {
        $this->sm = $sm;
    }
	
    public function listBooks($filters)
    {
    	$adapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sqlString = 'SELECT books_table.* FROM books_table';
        
        if (is_array($filters))
            if (count($filters) > 0){
                if (array_key_exists('genre', $filters)){
                    $sqlString .= ' LEFT JOIN ( SELECT book_id, COUNT(book_id) as genre_filter FROM genres_table WHERE ';
                    foreach ($filters['genre'] as $value):
                        $sqlString .= 'genre = "' . $value . '" OR ';
                    endforeach;
                    $sqlString = substr($sqlString, 0, -3);
                    $sqlString .= 'GROUP BY book_id) B ON books_table.book_id = B.book_id WHERE B.genre_filter = ' . count($filters['genre']) . '  AND ';
                } else 
                    $sqlString .= ' WHERE ';
                
                foreach ($filters as $key => $value):
                    if ($key == 'key'){
                        foreach ($value as $subValue):
                    	    $sqlString .= 'UPPER(CONCAT(title, author)) LIKE "%' . mb_strtoupper ($subValue) . '%"  AND ';
                    	endforeach;
                    } elseif (($key == 'user_id') || ($key == 'language') || ($key == 'serie') || ($key == 'author') || ($key == 'book_id')){
                    	$sqlString .= $key . ' = "' . $value . '"  and ';
                    } elseif ($key == 'sql') {
                    	$sqlString .= $value . '  and ';
                    }
                endforeach;
                $sqlString = substr($sqlString, 0, -6) . ' ORDER BY books_table.serie, books_table.volume, books_table.title';                
            }
        
    	$results = $adapter->query($sqlString, $adapter::QUERY_MODE_EXECUTE);
    
    	return $results;
    }
    
    
    public function listParams($filters, $param, $count_order = false, $filter_result = false)
    {
    	$adapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        //Setting up filter    	
    	$sqlString = '';
    	if (is_array($filters))
    	if (count($filters) > 0){
    		if (array_key_exists('genre', $filters)){
    			$sqlString .= ' LEFT JOIN ( SELECT book_id, COUNT(book_id) as genre_filter FROM genres_table WHERE ';
    			foreach ($filters['genre'] as $value):
    			$sqlString .= 'genre = "' . $value . '" OR ';
    			endforeach;
    			$sqlString = substr($sqlString, 0, -3);
    			$sqlString .= 'GROUP BY book_id) B ON books_table.book_id = B.book_id WHERE B.genre_filter = ' . count($filters['genre']) . '  AND ';
    		} else
    			$sqlString .= ' WHERE ';
    			
            foreach ($filters as $key => $value):
                if ($key == 'key'){
                    foreach ($value as $subValue):
                 	    $sqlString .= 'UPPER(CONCAT(title, author)) LIKE "%' . mb_strtoupper ($subValue) . '%"  AND ';
                  	endforeach;
                } elseif (($key == 'user_id') || ($key == 'language') || ($key == 'serie') || ($key == 'author') || ($key == 'book_id')){
                  	$sqlString .= $key . ' = "' . $value . '"  and ';
                }
            endforeach;
    		$sqlString = substr($sqlString, 0, -6);
    	}
    	 
    	//Adjusting SQL
    	if ($param !== 'genre'){
    	    $sqlString = 'SELECT books_table.' . $param . ', COUNT(books_table.book_id) as count FROM books_table' . $sqlString;
    	    $sqlString .= ' GROUP BY ' . $param;
    	} else {
     	    $sqlString = 'SELECT books_table.book_id FROM books_table' . $sqlString;
     	    $sqlString = 'SELECT genres_table.genre, COUNT(genres_table.book_id) as count FROM genres_table LEFT JOIN (' . $sqlString;
     	    $sqlString .= ') C ON genres_table.book_id = C.book_id WHERE C.book_id IS NOT NULL GROUP BY genres_table.genre';
    	}
    	$sqlString .= ($count_order ? ' ORDER BY count DESC' : ' ORDER BY ' . $param . ' ASC');
    	 
    	//        var_dump($sqlString);
    	$results = $adapter->query($sqlString, $adapter::QUERY_MODE_EXECUTE)->toArray();
    	
    	//Filter results
    	if ($filter_result)
    		for ($i = count($results) - 1; $i >= 0; $i--):
	    		if (!$results[$i][$param])
    				unset($results[$i]);
    		endfor;
//    		var_dump($results);
    	 
    
    	return $results;
    }
    
    public function addBook($book, $genres = null)
    {
    	$adapter = $this->sm->get('Zend\Db\Adapter\Adapter');
    	$sql = new Sql($adapter);
    
    	$book->book_id = null;
    	$insert = $sql->insert('books_table')->values(array(
    			'user_id'  => $book->user_id,
    	        'title'    => $book->title,
    			'author'   => $book->author,
    			'serie'    => $book->serie,
    			'volume'   => $book->volume,
    			'year'     => $book->year,
    			'date'     => $book->date,
    			'language' => $book->language,
    			'sinopse'  => $book->sinopse,
    			'status'   => $book->status
    	));
    
    	$selectString = $sql->getSqlStringForSqlObject($insert);
    	$adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE);
    	$book->book_id = $adapter->getDriver()->getLastGeneratedValue();
    
    	if ($genres)
    	foreach ($genres as $genre) :
    	    $insert = $sql->insert('genres_table')->values(array(
    			'genre'    => $genre,
    			'book_id'  => $book->book_id
    	    ));
    	    $selectString = $sql->getSqlStringForSqlObject($insert);
    	    $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE);
    	endforeach;
    
    	return $book;
    }
    
    public function listAuthors($key)
    {
    	$adapter = $this->sm->get('Zend\Db\Adapter\Adapter');
    	$sqlString = 'SELECT author FROM books_table WHERE status = \'ok\' %s GROUP BY author';
    	$where = '';
    	 
    	$key = str_replace('.', ' ', $key);
    	$key = str_replace(',', ' ', $key);
		$key = strtolower($key);
    	$keys = explode(' ', trim($key));
    	 
    	if (count($keys) > 0) {
    		foreach ($keys as $term):
				$where = $where . 'LOWER(author) LIKE \'%' . $term . '%\' AND ';    			
    		endforeach;
    		$where = 'AND ( '. substr($where, 0, -5) . ' )';
    	}
    	
    	$sqlString = sprintf($sqlString, $where);

    	$results = $adapter->query($sqlString, $adapter::QUERY_MODE_EXECUTE)->toArray();
    	return $results;
    }
    
    
    public function editBook($book, $genres)
    {
    	$adapter = $this->sm->get('Zend\Db\Adapter\Adapter');
    	$sql = new Sql($adapter);
    
    	$update = $sql->update('books_table')->set(array(
    			'title'    => $book->title,
    			'author'   => $book->author,
    			'serie'    => $book->serie,
    			'volume'   => $book->volume,
    			'year'     => $book->year,
    			'date'     => $book->date,
    			'language' => $book->language,
    			'sinopse'  => $book->sinopse,
    			'status'   => $book->status
    	))->where(array('book_id' => $book->book_id));
    
    	$selectString = $sql->getSqlStringForSqlObject($update);
    	$adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE);
    
    	$delete = $sql->delete('genres_table')->where(array('book_id' => $book->book_id));
    	$selectString = $sql->getSqlStringForSqlObject($delete);
    	$adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE);
    
    	if ($genres){
    		foreach ($genres as $genre) :
    		$insert = $sql->insert('genres_table')->values(array(
    				'genre'    => $genre,
    				'book_id'  => $book->book_id
    		));
    		$selectString = $sql->getSqlStringForSqlObject($insert);
    		$adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE);
    		endforeach;
    	}
    
    	return $book;
    }
    
    public function delBook($book_id)
    {
    	$adapter = $this->sm->get('Zend\Db\Adapter\Adapter');
    	$sql = new Sql($adapter);
    
    	$delete = $sql->delete('books_table')->where(array('book_id' => $book_id));
    	$selectString = $sql->getSqlStringForSqlObject($delete);
    	$adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE);
    	 
    	$delete = $sql->delete('genres_table')->where(array('book_id' => $book_id));
    	$selectString = $sql->getSqlStringForSqlObject($delete);
    	$adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE);
    
    	return 'Book successfully deleted';
    }
}
