-- --------------------------------------------------------
-- Host:                         5.134.124.172
-- Server version:               5.5.55 - MySQL Community Server (GPL) by Atomicorp
-- Server OS:                    Linux
-- HeidiSQL Version:             9.5.0.5196
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Dumping data for table eetest.products: ~4 rows (approximately)
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` (`id`, `name`, `category`, `description`, `pictures`, `documents`, `in_stock`, `size`, `color`, `price`) VALUES
	(1, 'Motherboard XC115', 1, 'Lorem ipsum dolor sit amet, cum ea nominavi pericula constituto, quo latine numquam deseruisse ex, iisque omittam similique sit no.', '013.jpg;014.jpg', 'hemingway ernest - il vecchio e il mare.pdf;il gabbiano jonathan livingston.pdf', 1, 'Medium', 'red', 25),
	(2, 'WEBSTUDIO', 2, 'Lorem ipsum dolor sit amet, cum ea nominavi pericula constituto, quo latine numquam deseruisse ex, iisque omittam similique sit no.', NULL, NULL, NULL, 'Medium', 'yellow', 31),
	(6, 'FormProcessor', 2, '', '08.jpg;010.jpg', 'hemingway ernest - il vecchio e il mare.pdf;il gabbiano jonathan livingston.pdf', NULL, 'Medium', 'fuchsia', 12000),
	(7, 'Detroit', 3, '', 'hachiko.jpg', NULL, 1, 'Large', 'red', 25);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
