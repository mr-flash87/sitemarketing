-- AgroMina : catalogue de départ (repris du site Lovable)
SET NAMES utf8mb4;

INSERT IGNORE INTO products (slug, name, category, price, old_price, stock, weight, description, is_new, best_seller, is_active, image_url, created_at) VALUES
('arraw-mil', 'Arraw de mil', 'cereales', 1200, NULL, 10, '500 g', 'Granules de mil pour bouillie traditionnelle.', 1, 0, 1, NULL, '2026-11-12 14:18:37'),
('confiture-mangue', 'Confiture de mangue', 'fruits-legumes', 2000, NULL, 19, '250 g', 'Mangues mûres du Saloum cuites lentement au chaudron.', 0, 1, 1, NULL, '2026-11-12 14:18:37'),
('crevettes-sechees', 'Crevettes séchées', 'halieutiques', 4500, NULL, 8, '250 g', 'Crevettes du Delta séchées, idéales pour relever vos sauces.', 1, 0, 1, NULL, '2026-11-12 14:18:37'),
('fonio-precuit', 'Fonio précuit', 'cereales', 2500, 3000, 25, '1 kg', 'Fonio lavé et précuit, riche en fibres, cuisson en 5 minutes.', 0, 0, 1, NULL, '2026-11-12 14:18:37'),
('jus-bissap', 'Jus de bissap', 'jus', 1000, NULL, 60, '75 cl', 'Infusion d''hibiscus rouge, légèrement sucrée, sans conservateur.', 0, 1, 1, NULL, '2026-11-12 14:18:37'),
('jus-bouye', 'Jus de bouye (baobab)', 'jus', 1200, 1500, 35, '75 cl', 'Pulpe de fruit de baobab onctueuse, riche en vitamine C.', 0, 1, 1, NULL, '2026-11-12 14:18:37'),
('jus-gingembre', 'Jus de gingembre', 'jus', 1000, NULL, 30, '75 cl', 'Gingembre frais pressé, citron et une touche de menthe.', 1, 0, 1, NULL, '2026-11-12 14:18:37'),
('mangue-sechee', 'Mangue séchée', 'fruits-legumes', 1800, 2200, 15, '100 g', 'Tranches de mangue séchées au soleil, sans sucre ajouté.', 1, 0, 1, NULL, '2026-11-12 14:18:37'),
('miel-saloum', 'Miel des mangroves', 'autres', 4000, NULL, 10, '500 g', 'Miel récolté dans les mangroves du Saloum.', 1, 0, 1, NULL, '2026-11-12 14:18:37'),
('pate-arachide', 'Pâte d''arachide', 'artisanaux', 1500, NULL, 30, '350 g', 'Arachides grillées et broyées finement, 100 % naturelle.', 0, 0, 1, NULL, '2026-11-12 14:18:37'),
('poisson-fume', 'Poisson fumé (kéthiakh)', 'halieutiques', 3500, NULL, 12, '500 g', 'Sardinelles fumées artisanalement par les femmes transformatrices.', 0, 1, 1, NULL, '2026-11-12 14:18:37'),
('poudre-baobab', 'Poudre de baobab (bouye)', 'artisanaux', 2000, 2500, 22, '200 g', 'Pulpe de baobab séchée et tamisée, super-aliment local.', 0, 0, 1, NULL, '2026-11-12 14:18:37'),
('sel-saloum', 'Sel de mer du Saloum', 'autres', 800, NULL, 50, '500 g', 'Sel récolté artisanalement dans les salines du Delta.', 0, 0, 1, NULL, '2026-11-12 14:18:37'),
('thiakry-mil', 'Thiakry de mil', 'cereales', 1500, 3000, 39, '500 g', 'Couscous de mil roulé à la main, prêt à préparer avec du lait caillé.', 0, 1, 1, NULL, '2026-11-12 14:18:37');
