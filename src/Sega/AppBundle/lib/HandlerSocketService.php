<?php

namespace Dcs;

use Closure;
use Gaia\Bundle\CommonBundle\Exception\ErrorCodes;
use Gaia\Bundle\CommonBundle\Exception\GaiaException;
use Gaia\Bundle\HandlerSocketBundle\HandlerSocket\HandlerSocket;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Dcs\AnalysisCounter as AC;

/**
 * ハンドラソケットサービスクラス
 *
 * DCS用に改造
 *
 * MySQL Handler Socket のクライアントライブラリ(PHPエクステンション)
 * {@link https://code.google.com/p/php-handlersocket/ php-handlersocket}
 * を使用し、handler socket を利用したデータベースアクセスを行う。
 *
 * @copyright Copyright (c) 2013 SEGA Networks Co., Ltd. All rights reserved.
 * @author Itec Hokkaido
 * @author takeday
 */
class HandlerSocketService implements \Gaia\Bundle\HandlerSocketBundle\Service\HandlerSocketServiceInterface{
	/**
	 * @var HandlerSocket|array 読取用ハンドラソケット
	 */
	protected $readSocket;
	
	/**
	 * @var HandlerSocket 読取用ハンドラソケットオープン済み
	 */
	protected $openedReadSocket;
	
	/**
	 * @var HandlerSocket 書込用ハンドラソケット
	 */
	protected $writeSocket;
	
	/**
	 * @var HandlerSocket 読取用ハンドラソケットオープン済み
	 */
	protected $openedWriteSocket;
	
	/**
	 * コンストラクタ
	 *
	 * @param HandlerSocket|array $readSocket
	 *        	読取用ハンドラソケット
	 * @param HandlerSocket $writeSocket
	 *        	書込用ハンドラソケット
	 */
	public function __construct($readSocket,HandlerSocket $writeSocket){
		$this->readSocket = $readSocket;
		$this->writeSocket = $writeSocket;
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function select(Table $table,Query $query){
		$this->selectLog($table,$query);
		
		return $this->read($table,function ($index) use($query){
			return $index->find($query->query,$query->limit,$query->offset,$query->options);
		});
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function selectMulti(Table $table,array $queries){
		$this->selectMultiLog($table,$queries);
		$param = $this->createSelectMultiParam($queries);
		
		return $this->read($table,function ($index) use($param){
			return $index->multi($param);
		});
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function insert(Table $table,array $value){
		$this->insertLog($table,$value);
		
		return $this->write($table,function ($index) use($value){
			return call_user_func_array(array(
				$index,
				'insert'
			),$value);
		});
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function insertMulti(Table $table,array $values){
		$this->insertMultiLog($table,$values);
		$param = $this->createInsertMultiParam($values);
		
		return $this->write($table,function ($index) use($param){
			return $index->multi($param);
		});
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function update(Table $table,Query $query,array $value){
		$this->updateLog($table,$query,$value);
		
		return $this->write($table,function ($index) use($query,$value){
			return $index->update($query->query,$value,$query->limit,$query->offset,$query->options);
		});
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function updateMulti(Table $table,array $queries,array $values){
		$this->updateMultiLog($table,$queries,$values);
		$param = $this->createUpdateMultiParam($queries,$values);
		
		return $this->write($table,function ($index) use($param){
			return $index->multi($param);
		});
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function delete(Table $table,Query $query){
		$this->deleteLog($table,$query);
		
		return $this->write($table,function ($index) use($query){
			return $index->remove($query->query,$query->limit,$query->offset,$query->options);
		});
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function deleteMulti(Table $table,array $queries){
		$this->deleteMultiLog($table,$queries);
		$param = $this->createDeleteMultiParam($queries);
		
		return $this->write($table,function ($index) use($param){
			return $index->multi($param);
		});
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function read($table,callable $function){
		DetailTimeLog::Lap('hs read '.$table->table);
		
		$result = $this->readSocket()->execute($table,$function);
		
		DetailTimeLog::Lap('hs read end');
		
		return $result;
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function write($table,callable $function){
		DetailTimeLog::Lap('hs write '.$table->table);
		
		$result = $this->writeSocket()->execute($table,$function);
		
		DetailTimeLog::Lap('hs write end');
		
		return $result;
	}
	
	/**
	 * 書込ソケット取得
	 */
	private function writeSocket(){
		if(is_null($this->openedWriteSocket)){
			$socket = $this->writeSocket;
			$socket->openSocket();
			$this->openedWriteSocket = $socket;
		}
		
		return $this->openedWriteSocket;
	}
	
	/**
	 * 読込ソケット取得
	 */
	private function readSocket(){
		if(is_null($this->openedReadSocket)){
			$socket = is_array($this->readSocket)?$this->readSocket[mt_rand(0,count($this->readSocket)-1)]:$this->readSocket;
			$socket->openSocket();
			$this->openedReadSocket = $socket;
		}
		
		return $this->openedReadSocket;
	}
	
	/**
	 * log
	 */
	private function selectLog(Table $table){
		AC::hs_table($table->table);
		AC::hs_select();
	}
	
	/**
	 * log
	 */
	private function selectMultiLog(Table $table){
		AC::hs_table($table->table);
		AC::hs_select_multi();
	}
	
	/**
	 * log
	 */
	private function insertLog(Table $table){
		AC::hs_table($table->table);
		AC::hs_insert();
	}
	
	/**
	 * log
	 */
	private function insertMultiLog(Table $table){
		AC::hs_table($table->table);
		AC::hs_insert_multi();
	}
	
	/**
	 * log
	 */
	private function updateLog(Table $table){
		AC::hs_table($table->table);
		AC::hs_update();
	}
	
	/**
	 * log
	 */
	private function updateMultiLog(Table $table){
		AC::hs_table($table->table);
		AC::hs_update_multi();
	}
	
	/**
	 * log
	 */
	private function deleteLog(Table $table,Query $query){
		AC::hs_table($table->table);
		AC::hs_delete();
	}
	
	/**
	 * log
	 */
	private function deleteMultiLog(Table $table){
		AC::hs_table($table->table);
		AC::hs_delete_multi();
	}
	
	/**
	 * create parameter
	 */
	private function createSelectMultiParam($queries){
		$param = array();
		foreach($queries as $query){
			$param[] = array(
				'find',
				$query->query,
				$query->limit,
				$query->offset,
				$query->options
			);
		}
		return $param;
	}
	
	/**
	 * create parameter
	 */
	private function createInsertMultiParam($values){
		$param = array();
		foreach($values as $value){
			$param[] = array(
				'insert',
				$value
			);
		}
		return $param;
	}
	
	/**
	 * create parameter
	 */
	private function createUpdateMultiParam($queries,$values){
		$param = array();
		for($i = 0;$i<count($queries);$i++){
			$param[] = array(
				'update',
				$queries[$i]->query,
				$values[$i],
				$queries[$i]->limit,
				$queries[$i]->offset,
				$queries[$i]->options
			);
		}
		return $param;
	}
	
	/**
	 * create parameter
	 */
	private function createDeleteMultiParam($queries){
		$param = array();
		foreach($queries as $query){
			$param[] = array(
				'remove',
				$query->query,
				$query->limit,
				$query->offset,
				$query->options
			);
		}
		return $param;
	}
}