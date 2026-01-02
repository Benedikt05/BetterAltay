<?php

/*
 * AUTO-GENERATED FILE - DO NOT EDIT MANUALLY
 */

declare(strict_types=1);

namespace pocketmine\maps\renderer;

use pocketmine\block\Block;
use pocketmine\utils\Color;
use const pocketmine\RESOURCE_PATH;
use function file_exists;
use function file_get_contents;
use function json_decode;

class MapColorTable{

	private static array $mapping = [];
	private static ?Color $C_TRANSPARENT = null;
	private static ?Color $C_112_112_112 = null;
	private static ?Color $C_151_109_77 = null;
	private static ?Color $C_255_252_245 = null;
	private static ?Color $C_127_178_56 = null;
	private static ?Color $C_129_86_49 = null;
	private static ?Color $C_143_119_72 = null;
	private static ?Color $C_247_233_163 = null;
	private static ?Color $C_216_127_51 = null;
	private static ?Color $C_209_177_161 = null;
	private static ?Color $C_102_76_51 = null;
	private static ?Color $C_153_51_51 = null;
	private static ?Color $C_229_229_51 = null;
	private static ?Color $C_0_124_0 = null;
	private static ?Color $C_242_127_165 = null;
	private static ?Color $C_167_167_167 = null;
	private static ?Color $C_64_64_255 = null;
	private static ?Color $C_255_0_0 = null;
	private static ?Color $C_100_100_100 = null;
	private static ?Color $C_112_2_0 = null;
	private static ?Color $C_76_76_76 = null;
	private static ?Color $C_57_41_35 = null;
	private static ?Color $C_160_77_78 = null;
	private static ?Color $C_74_128_255 = null;
	private static ?Color $C_255_255_255 = null;
	private static ?Color $C_178_76_216 = null;
	private static ?Color $C_102_153_216 = null;
	private static ?Color $C_127_204_25 = null;
	private static ?Color $C_153_153_153 = null;
	private static ?Color $C_76_127_153 = null;
	private static ?Color $C_127_63_178 = null;
	private static ?Color $C_51_76_178 = null;
	private static ?Color $C_102_127_51 = null;
	private static ?Color $C_25_25_25 = null;
	private static ?Color $C_199_199_199 = null;
	private static ?Color $C_250_238_77 = null;
	private static ?Color $C_92_219_213 = null;
	private static ?Color $C_148_63_97 = null;
	private static ?Color $C_58_142_140 = null;
	private static ?Color $C_160_160_255 = null;
	private static ?Color $C_164_168_184 = null;
	private static ?Color $C_135_107_98 = null;
	private static ?Color $C_127_167_150 = null;
	private static ?Color $C_159_82_36 = null;
	private static ?Color $C_0_217_58 = null;
	private static ?Color $C_149_87_108 = null;
	private static ?Color $C_112_108_138 = null;
	private static ?Color $C_186_133_36 = null;
	private static ?Color $C_103_117_53 = null;
	private static ?Color $C_87_92_92 = null;
	private static ?Color $C_122_73_88 = null;
	private static ?Color $C_76_62_92 = null;
	private static ?Color $C_76_50_35 = null;
	private static ?Color $C_76_82_42 = null;
	private static ?Color $C_142_60_46 = null;
	private static ?Color $C_37_22_16 = null;
	private static ?Color $C_86_44_62 = null;
	private static ?Color $C_22_126_134 = null;
	private static ?Color $C_20_180_133 = null;
	private static ?Color $C_92_25_29 = null;
	private static ?Color $C_189_48_49 = null;
	private static ?Color $C_216_175_147 = null;

	public static function getColor(Block $block) : Color{
		if(empty(self::$mapping)){
			$path = RESOURCE_PATH . '/vanilla/map_colors.json';
			if(!file_exists($path)) return new Color(0, 0, 0, 0);
			self::$mapping = json_decode(file_get_contents($path), true) ?? [];
		}

		$key = self::$mapping[$block->getId()] ?? null;

		return match ($key) {
			'112_112_112' => self::$C_112_112_112 ??= new Color(112, 112, 112),
			'151_109_77' => self::$C_151_109_77 ??= new Color(151, 109, 77),
			'255_252_245' => self::$C_255_252_245 ??= new Color(255, 252, 245),
			'127_178_56' => self::$C_127_178_56 ??= new Color(127, 178, 56),
			'129_86_49' => self::$C_129_86_49 ??= new Color(129, 86, 49),
			'143_119_72' => self::$C_143_119_72 ??= new Color(143, 119, 72),
			'247_233_163' => self::$C_247_233_163 ??= new Color(247, 233, 163),
			'216_127_51' => self::$C_216_127_51 ??= new Color(216, 127, 51),
			'209_177_161' => self::$C_209_177_161 ??= new Color(209, 177, 161),
			'102_76_51' => self::$C_102_76_51 ??= new Color(102, 76, 51),
			'153_51_51' => self::$C_153_51_51 ??= new Color(153, 51, 51),
			'229_229_51' => self::$C_229_229_51 ??= new Color(229, 229, 51),
			'0_124_0' => self::$C_0_124_0 ??= new Color(0, 124, 0),
			'242_127_165' => self::$C_242_127_165 ??= new Color(242, 127, 165),
			'167_167_167' => self::$C_167_167_167 ??= new Color(167, 167, 167),
			'64_64_255' => self::$C_64_64_255 ??= new Color(64, 64, 255),
			'255_0_0' => self::$C_255_0_0 ??= new Color(255, 0, 0),
			'100_100_100' => self::$C_100_100_100 ??= new Color(100, 100, 100),
			'112_2_0' => self::$C_112_2_0 ??= new Color(112, 2, 0),
			'76_76_76' => self::$C_76_76_76 ??= new Color(76, 76, 76),
			'57_41_35' => self::$C_57_41_35 ??= new Color(57, 41, 35),
			'160_77_78' => self::$C_160_77_78 ??= new Color(160, 77, 78),
			'74_128_255' => self::$C_74_128_255 ??= new Color(74, 128, 255),
			'255_255_255' => self::$C_255_255_255 ??= new Color(255, 255, 255),
			'178_76_216' => self::$C_178_76_216 ??= new Color(178, 76, 216),
			'102_153_216' => self::$C_102_153_216 ??= new Color(102, 153, 216),
			'127_204_25' => self::$C_127_204_25 ??= new Color(127, 204, 25),
			'153_153_153' => self::$C_153_153_153 ??= new Color(153, 153, 153),
			'76_127_153' => self::$C_76_127_153 ??= new Color(76, 127, 153),
			'127_63_178' => self::$C_127_63_178 ??= new Color(127, 63, 178),
			'51_76_178' => self::$C_51_76_178 ??= new Color(51, 76, 178),
			'102_127_51' => self::$C_102_127_51 ??= new Color(102, 127, 51),
			'25_25_25' => self::$C_25_25_25 ??= new Color(25, 25, 25),
			'199_199_199' => self::$C_199_199_199 ??= new Color(199, 199, 199),
			'250_238_77' => self::$C_250_238_77 ??= new Color(250, 238, 77),
			'92_219_213' => self::$C_92_219_213 ??= new Color(92, 219, 213),
			'148_63_97' => self::$C_148_63_97 ??= new Color(148, 63, 97),
			'58_142_140' => self::$C_58_142_140 ??= new Color(58, 142, 140),
			'160_160_255' => self::$C_160_160_255 ??= new Color(160, 160, 255),
			'164_168_184' => self::$C_164_168_184 ??= new Color(164, 168, 184),
			'135_107_98' => self::$C_135_107_98 ??= new Color(135, 107, 98),
			'127_167_150' => self::$C_127_167_150 ??= new Color(127, 167, 150),
			'159_82_36' => self::$C_159_82_36 ??= new Color(159, 82, 36),
			'0_217_58' => self::$C_0_217_58 ??= new Color(0, 217, 58),
			'149_87_108' => self::$C_149_87_108 ??= new Color(149, 87, 108),
			'112_108_138' => self::$C_112_108_138 ??= new Color(112, 108, 138),
			'186_133_36' => self::$C_186_133_36 ??= new Color(186, 133, 36),
			'103_117_53' => self::$C_103_117_53 ??= new Color(103, 117, 53),
			'87_92_92' => self::$C_87_92_92 ??= new Color(87, 92, 92),
			'122_73_88' => self::$C_122_73_88 ??= new Color(122, 73, 88),
			'76_62_92' => self::$C_76_62_92 ??= new Color(76, 62, 92),
			'76_50_35' => self::$C_76_50_35 ??= new Color(76, 50, 35),
			'76_82_42' => self::$C_76_82_42 ??= new Color(76, 82, 42),
			'142_60_46' => self::$C_142_60_46 ??= new Color(142, 60, 46),
			'37_22_16' => self::$C_37_22_16 ??= new Color(37, 22, 16),
			'86_44_62' => self::$C_86_44_62 ??= new Color(86, 44, 62),
			'22_126_134' => self::$C_22_126_134 ??= new Color(22, 126, 134),
			'20_180_133' => self::$C_20_180_133 ??= new Color(20, 180, 133),
			'92_25_29' => self::$C_92_25_29 ??= new Color(92, 25, 29),
			'189_48_49' => self::$C_189_48_49 ??= new Color(189, 48, 49),
			'216_175_147' => self::$C_216_175_147 ??= new Color(216, 175, 147),
			default => self::$C_TRANSPARENT ??= new Color(0, 0, 0, 0)
		};
	}
}