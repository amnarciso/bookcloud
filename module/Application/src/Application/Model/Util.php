<?php 

namespace Application\Model;

class Util
{

/**
 * Update Filter Function
 * 
 * Receive a filter and the instructions, return updated filter
 * @param unknown $filter Filter to be changed
 * @param unknown $commands Instruction to update filter
 * @return Ambigous <multitype:NULL multitype: , unknown, multitype:>
 */
function updateFilter($filter, $commands)
{
    $result = $filter;
    if (is_array($commands))
    {
        //Go through all commands
        foreach ($commands as $key => $value):
            //Split operator and value
            $op = $key[0];
            $key = trim(substr($key, 1));
            //Only work if there is a value
            if ($value){
                //Delete value 
                if (($op == 'd') && (is_array($result))){
                    //Only delete if the key exists
                  	if (array_key_exists($key, $result)){
                  	    //If the key is 'key' or 'genre' need to exclude from inner array
                   		if (($key == 'key') || ($key == 'genre')){
                   		    //Only have to delete if the value is on the array
                   			if (($refKey = array_search($value, $result[$key])) !== false)
                   			    //Delete the value from inner array
                   				unset($result[$key][$refKey]);

                   		//If it is a commom key, only delete the key
                   		} elseif (($key == 'language') || ($key == 'user_id') || ($key == 'serie') || ($key == 'author')) {
               				unset($result[$key]);
                   		}
                   	}
                //Add or reset value
                } elseif ($op == 'a') {
                    //If there is no array yet, it creates one
                    if (!is_array($result))
                        $result = array($key => null);

                    //If the key doesn't exist yet, it creates it
                    if (!array_key_exists($key, $result))
                        $result = array_merge($result, array($key => null));

                    //If the key is 'genre', need to add value to inner array
                  	if ($key == 'genre'){
                  	    //If there is no inner array, it creates one
                        if (!is_array($result[$key]))
                            $result[$key] = array();
                  	    //Only add the value to inner array if it doesn't exist
                  	    if (($refKey = array_search($value, $result[$key])) == false)
                   			array_push($result[$key], $value);

                  	//If the key is 'key', it will need to include every word on the inner array
                   	} elseif ($key == 'key'){
                  	    //If there is no inner array, it creates one
                   	    if (!is_array($result[$key]))
                            $result[$key] = array();
                   	    //Go through all the words
                        $valueList = explode(' ', $value);
                   		foreach ($valueList as $subValue):
                  	        //Only add the value to inner array if it doesn't exist
                   		    if (($refKey = array_search($subValue, $result[$key])) == false)
                   			    array_push($result[$key], $subValue);
                   		endforeach;

                    //If it is a commom key, just change the value
                   	} elseif (($key == 'language') || ($key == 'user_id') || ($key == 'serie') || ($key == 'author')) {
                        $result = Util::updateParam($result, array($key => $value));
                   	}
                }
            }
        endforeach;
    }
    
    //Delete keys with empty inner array
    if (is_array($filter)){
        if (array_key_exists('key', $filter))
            if ($result['key'] == null)
                unset($result['key']);
            
        if (array_key_exists('genre', $filter))
            if ($result['genre'] == null)
                unset($result['genre']);
    }
    
    return $result;
}

/**
 * Update Params Function
 * 
 * Array [$new_params] will be merged to array [$old_params], overwriting params with the same name
 * @param array $old_params Existing Array
 * @param array $new_params Array with parameters to be updated
 * @return array
 */
function updateParam($old_params, $new_params)
{
    $params = $old_params;
    
	if (is_array($params)){
	    foreach($new_params as $key => $value):    	
	        if (array_key_exists($key, $params))
			    $params[$key] = $value;
		    else
			    $params = array_merge($params, array($key => $value));
		endforeach;
	} else {
		$params = $new_params;
	}

	return $params;
}

/**
 * Add Query Function
 * 
 * Add array of params as a query to the end of an URL
 * INPUT URL MUST NOT HAVE ANY QUERY
 * @param string $url URL to be updated
 * @param array $params Array of parameters to be added
 * @return string
 */
function addQuery($url, $params)
{
    if (is_array($params)){
        if (count($params) > 0){
            $url .= '?';
            foreach ($params as $key => $value):
                $url .= $key . '=' . $value . '&';
            endforeach;
            $url = substr($url, 0, -1);
        }
    }    
    return $url;
}

function unique_id($l = 8) {
    return substr(md5(uniqid(mt_rand(), true)), 0, $l);
}
}