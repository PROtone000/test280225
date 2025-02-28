<?php
/**
 * Контроллер обработки получения и проверки скидки
 * Возвращает данные в JSON
 * Да, не PSR-4... просто так исторически сложилось
 */
class Controllers_JSON_Discount extends Kda_Controller_JSON
{
	protected function working()
	{
		/**
		 * Модель для парсинга текущего URL
		 * Например, /json/discount/get/ будет разбит на уровни
		 * 0: json
		 * 1: discount
		 * 2: get
		 * @var Kda_Urllevel $URLLevel
		 */
		$URLLevel = Kda_Urllevel::getInstance();
		
		$result = match ($URLLevel->Level(2)) {
			
			/**
			 * Обработка /json/discount/get/
			 * Получение скидки
			 */
			'get' => $this->getAction(),
			
			/**
			 * Обработка /json/discount/check/
			 * Проверка скидки
			 */
			'check'	=> $this->checkAction(),
			
			default => ['success' => 0, 'error' => 'Не верные входные данные']
		};
		
		return $result;
	}
	
	/**
	 * Получение скидки
	 * Входящих параметров в POST нет
	 * @return array
	 */
	private function getAction()
	{
		$result = ['success' => 0];
		
		/**
		 * Singleton, в котором содержится информация
		 * о текущем авторизованном пользователе
		 * @var Kda_User $user
		 */
		$user = Kda_UserActive::getInstance()->getUser();
		
		/**
		 * Авторизован ли текущий пользователь?
		 */
		if ($user->getId() == 0)
		{
			$result['error'] = 'Вы не авторизованы';
			return $result;
		}
		
		/**
		 * Объект доступа к данным таблицы скидок
		 * @var Dao_Discount $daoDiscount
		 */
		$daoDiscount = new Dao_Discount();
		
		/**
		 * Набор данных о самой последней скидке пользователя
		 * @var Kda_Db_DataSet $discountDS
		 */
		$discountDS = $daoDiscount->getLastByUser($user->getId());
		
		/**
		 * 1 час в секундах
		 * @var integer $interval
		 */
		$interval = 60 * 60;
		
		/**
		 * Скидка существует
		 * и была создана менее часа назад
		 * Вернём её, а не будем создавать новую
		 */
		if ($discountDS->Count() == 1
			&&
			$discountDS->Value('created') > (NOW - $interval))
		{
			$result['success'] = 1;
			$result['amount'] = $discountDS->Value('amount');
			$result['code'] = $discountDS->Value('code');
			
			return $result;
		}
		
		/**
		 * Если предыдущие условия не выполнены
		 * создадим новую скидку от 1 до 50
		 * и сгенерируем для неё уникальный код
		 */
		
		/**
		 * Для большей уверенности в уникальности кода
		 */
		do
		{
			/**
			 * Вроде на выходе должна получиться
			 * криптографически-стойкая строка,
			 * но если нужно совсем уникальное значение,
			 * то лучше воспользоваться API Randon.org
			 * @var string $code
			 */
			$code = bin2hex(random_bytes(10));
			/**
			 * Проверяем, есть ли уже такой код в таблице
			 * @var Kda_Db_DataSet $checkDS
			 */
			$checkDS = $daoDiscount->check($code);
		}
		while ($checkDS->Count() > 0);
		
		/**
		 * Величина скидки
		 * @var integer $amount
		 */
		$amount = mt_rand(1, 50);
		
		/**
		 * Сохранение скидки в БД
		 */
		$daoDiscount
			->addValue('userid',	$user->getId())
			->addValue('code',		$code)
			->addValue('amount', 	$amount)
			->addValue('created',	time())
			->add();
		
		/**
		 * Возвращаем пользователю величину скидки и код
		 */
		$result['success'] = 1;
		$result['amount'] = $amount;
		$result['code'] = $code;
		
		return $result;
	}
	
	/**
	 * Проверка скидки
	 * В POST ожидается параметр code - строка длинной 20 символов
	 * @return array
	 */
	private function checkAction()
	{
		$result = ['success' => 0];
		
		/**
		 * Singleton, в котором содержится информация
		 * о текущем авторизованном пользователе
		 * @var Kda_User $user
		 */
		$user = Kda_UserActive::getInstance()->getUser();
		
		/**
		 * Авторизован ли текущий пользователь?
		 */
		if ($user->getId() == 0)
		{
			$result['error'] = 'Вы не авторизованы';
			return $result;
		}
		
		/**
		 * Фильтрация POST-параметра code типа string
		 * @var string $code
		 */
		$code = Model_Params::prepareValue('code', 's');
		
		if ($code == ''
			||
			mb_strlen($code) != 20)
		{
			$result['error'] = 'Не указан код скидки';
			return $result;
		}
		
		/**
		 * Объект доступа к данным таблицы скидок
		 * @var Dao_Discount $daoDiscount
		 */
		$daoDiscount = new Dao_Discount();
		
		/**
		 * Набор данных об искомой скидке
		 * @var Kda_Db_DataSet $discountDS
		 */
		$discountDS = $daoDiscount->check($code);
		
		/**
		 * 3 часа в секундах
		 * @var integer $interval
		 */
		$interval = 60 * 60 * 3;
		
		/**
		 * Проверка существования скидки
		 * Cкидка должна принадлежать текущему пользователю
		 * и быть создана не более 3 часов назад
		 */
		if ($discountDS->Count() == 0
			||
			$discountDS->Value('userid') != $user->getId()
			||
			$discountDS->Value('created') < (NOW - $interval))
		{
			$result['error'] = 'Скидка недоступна';
			return $result;
		}
		
		/**
		 * Если все проверки пройдены - отображаем значение скидки
		 */
		$result['success'] = 1;
		$result['amount'] = $discountDS->Value('amount');
		
		return $result;
	}
}