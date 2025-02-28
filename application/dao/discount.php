<?php
class Dao_Discount extends Kda_Db_DAOCustom
{
	protected function init()
	{
		$this
			->setTableName('discount')
			->setFields([
				/**
				 * UNSIGNED BIGINT INDEX
				 * Уникальный идентификатор пользователя
				 */
				'userid',
				/**
				 * VARCHAR(20) UNIQUE
				 * Уникальный код скидки
				 */
				'code',
				/**
				 * UNSIGNED TINYINT
				 * Значение скидки от 1 до 50
				 */
				'amount',
				/**
				 * TIMESTAMP
				 * Метка времени создания скидки
				 */
				'created'
			]);
	}
	
	/**
	 * Получение последней скидки созданной указанным пользователем
	 * @param integer $userid Идентификатор пользователя
	 * @return Kda_Db_DataSet
	 */
	public function getLastByUser($userid)
	{
		return $this->query('
			SELECT `id`, `userid`, `code`, `amount`, `created`
			FROM `discount`
			WHERE `userid` = '.$userid.'
			ORDER BY `id` DESC
			LIMIT 1
		')->Begin();
	}
	
	/**
	 * Проверка существования кода скидки
	 * @param string $code Код скидки
	 * @return Kda_Db_DataSet
	 */
	public function check($code)
	{
		return $this->query('
			SELECT `id`, `userid`, `code`, `amount`, `created`
			FROM `discount`
			WHERE `code` = "'.$this->clearstringsql($code).'"
		')->Begin();
	}
	
	/**
	 * Метод для очистки устаревших скидок, которым более 7 дней
	 * В ТЗ про это ничего не сказано, так что интервал может быть другим
	 * Если скидки хранятся довольно долго, то иеет смысл добавить индекс по полю created, хотя...
	 * Выполняется в планировщике
	 */
	public function clean()
	{
		/**
		 * 7 дней в секундах
		 * @var integer $interval
		 */
		$interval = 60 * 60 * 24 * 7;
		
		$this->query('
			DELETE FROM `discount`
			WHERE `created` < '.$interval.'
		');
	}
}