CREATE TABLE `users` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `username` varchar(255) UNIQUE NOT NULL,
  `password` varchar(255) NOT NULL,
  `firstName` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `email` varchar(255) UNIQUE,
  `phone` varchar(255) NOT NULL,
  `createdAt` timestamp NOT NULL
);

CREATE TABLE `pictures` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `ownerId` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `isExternal` boolean NOT NULL,
  `URL` varchar(255) NOT NULL,
  `visibility` boolean NOT NULL,
  `createdAt` timestamp NOT NULL
);

CREATE TABLE `pictureVotes` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `pictureId` int NOT NULL,
  `ownerId` int NOT NULL,
  `isPositive` boolean NOT NULL,
  `createdAt` timestamp NOT NULL
);

CREATE TABLE `pictureComments` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `pictureId` int NOT NULL,
  `ownerId` int NOT NULL,
  `comment` varchar(255) NOT NULL,
  `createdAt` timestamp NOT NULL
);

CREATE TABLE `usersFollowers` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `followedUser` int NOT NULL,
  `followerId` int NOT NULL,
  `createdAt` timestamp NOT NULL
);

CREATE TABLE `pictureTags` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `pictureId` int NOT NULL,
  `tagId` int NOT NULL,
  `createdAt` timestamp NOT NULL
);

CREATE TABLE `tags` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `tag` varchar(255) UNIQUE NOT NULL,
  `createdAt` timestamp NOT NULL
);

ALTER TABLE `pictures` ADD FOREIGN KEY (`ownerId`) REFERENCES `users` (`id`);

ALTER TABLE `pictureVotes` ADD FOREIGN KEY (`pictureId`) REFERENCES `pictures` (`id`);

ALTER TABLE `pictureComments` ADD FOREIGN KEY (`pictureId`) REFERENCES `pictures` (`id`);

ALTER TABLE `pictureComments` ADD FOREIGN KEY (`ownerId`) REFERENCES `users` (`id`);

ALTER TABLE `pictureVotes` ADD FOREIGN KEY (`ownerId`) REFERENCES `users` (`id`);

ALTER TABLE `usersFollowers` ADD FOREIGN KEY (`followedUser`) REFERENCES `users` (`id`);

ALTER TABLE `usersFollowers` ADD FOREIGN KEY (`followerId`) REFERENCES `users` (`id`);

ALTER TABLE `pictureTags` ADD FOREIGN KEY (`tagId`) REFERENCES `tags` (`id`);

ALTER TABLE `pictureTags` ADD FOREIGN KEY (`pictureId`) REFERENCES `pictures` (`id`);

CREATE UNIQUE INDEX `pictureVotes_index_0` ON `pictureVotes` (`ownerId`, `pictureId`);

CREATE UNIQUE INDEX `usersFollowers_index_1` ON `usersFollowers` (`followedUser`, `followerId`);

CREATE UNIQUE INDEX `pictureTags_index_2` ON `pictureTags` (`pictureId`, `tagId`);
