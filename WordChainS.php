<?php

/**
 * @name WordChainS
 * @main WordChainS\WordChainS
 * @author Ne0sW0rld
 * @version Master - Beta 1
 * @api 4.0.0
 * @description (!)
 */


namespace WordChainS;


use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\Server;

use pocketmine\utils\Internet;
use pocketmine\event\player\PlayerChatEvent;

use pocketmine\scheduler\ClosureTask;


class WordChainS extends PluginBase implements Listener
{

    protected $key = '여기에 넣으세요';
    protected $storage = [];

    protected $startingWords = ['도라지', '나비'];

    public function onEnable()
    {

        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask (function (int $currentTick): void {

            $this->gameData = [

                'last-word' => $this->startingWords [mt_rand(0, count($this->startingWords) - 1)],
                'joined-players' => []

            ];

            $w = $this->gameData ['last-word'];
            $this->gameData ['used-words'] = [$w];

            Server::getInstance()->broadcastMessage("§b§l[끝말잇기] §r§7새로운 끝말잇기 게임이 시작되었습니다! 제시된 시작 단어는 §e\"{$w}\" §7입니다.\n§b§l[끝말잇기] §r§7채팅창에 §e!(단어) §7를 입력하시면 됩니다.");

        }), 20 * 60 * 5);

        $this->getServer()->getPluginManager()->registerEvents($this, $this);

    }

    public function isExistWord(string $word)
    {

        if (isset ($this->storage [$word]))
            return $this->storage [$word];

        $result = Internet::getURL('https://stdict.korean.go.kr/api/search.do?certkey_no=1615&key=' . $this->key . '&type_search=search&q=' . urlencode($word));
        return ($this->storage [$word] = count((json_decode(json_encode(simplexml_load_string($result)), true)['item'] ?? [])) > 0);

    }

    public function onChat(PlayerChatEvent $event)
    {

        $player = $event->getPlayer();
        $chat = $event->getMessage();

        if (mb_substr($chat, 0, 1, 'utf-8') !== '!')
            return;

        $event->setCancelled(true);
        $writtenWord = trim($chat, '!');

        if (in_array($writtenWord, $this->gameData ['used-words'])) {

            $player->sendMessage('§b§l[끝말잇기] §r§7이 단어는 이미 사용되었습니다.');
            return;

        }

        if (in_array($player->getName(), $this->gameData ['joined-players'])) {

            $player->sendMessage('§b§l[끝말잇기] §r§7이번 회차에 이미 참가하였습니다.');
            return;

        }

        $lastWord = $this->gameData ['last-word'];
        $startingLetter = mb_substr($lastWord, 0, 1, 'utf-8');

        if ($startingLetter !== mb_substr($writtenWord, 0, 1, 'utf-8')) {

            $player->sendMessage('§b§l[끝말잇기] §r§7' . $startingLetter . '(으)로 시작하는 단어여야 합니다.');
            return;

        }

        if (!$this->isExistWord($writtenWord)) {
            $player->sendMessage("§b§l[끝말잇기] §r§7{$writtenWord}(은)는 사전에 존재하지 않습니다.");
            return;
        }

        Server::getInstance()->broadcastMessage("§b§l[끝말잇기] §r§7끝말잇기에서 {$player->getName()}님이 §e\"{$writtenWord}\" §7(을)를 외치셨습니다.");

        $this->gameData ['last-word'] = $writtenWord;
        $this->gameData ['used-words'][] = $writtenWord;
        $this->gameData ['joined-players'][] = $player->getName();

    }

}

?>