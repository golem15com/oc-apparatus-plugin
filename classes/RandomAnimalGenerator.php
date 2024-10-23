<?php 

namespace Golem15\Apparatus\Classes;

class RandomAnimalGenerator
{
    private const ADJECTIVES = [
        'agile', 'brave', 'calm', 'daring', 'eager', 'fierce', 'gentle', 'happy', 'jolly', 'kind', 'lively',
        'mighty', 'noble', 'quick', 'sharp', 'wise', 'zealous', 'crazy', 'curious', 'clever', 'diligent', 'friendly',
        'generous', 'honest', 'jovial', 'keen', 'loyal', 'merry', 'nifty', 'optimistic', 'polite', 'quirky', 'radiant',
        'sincere', 'thoughtful', 'upbeat', 'vibrant', 'witty', 'youthful', 'zany', 'active', 'bold', 'cheerful',
        'determined', 'energetic', 'fearless', 'graceful', 'humble', 'imaginative', 'joyful', 'kindhearted',
        'luminous', 'modest', 'neat', 'outgoing', 'patient', 'quiet', 'resourceful', 'spirited', 'trustworthy',
        'understanding', 'valiant', 'warm', 'adventurous', 'bright', 'charming', 'devoted', 'enthusiastic',
        'fearsome', 'gracious', 'honorable', 'intrepid', 'just', 'knowledgeable', 'lucky', 'motivated', 'noblehearted',
        'observant', 'persevering', 'resilient', 'sagacious', 'steadfast', 'tenacious', 'unique', 'vigilant',
        'welcoming', 'zestful', 'affable', 'brilliant', 'compassionate', 'dedicated', 'excited', 'flourishing',
        'generous', 'honorable', 'inventive', 'jubilant', 'kindly', 'lovable', 'marvelous', 'nurturing', 'optimistic',
        'playful', 'quaint', 'radiant', 'savvy', 'tactful', 'undaunted', 'valorous', 'wisehearted', 'zestful', 'artful',
        'bubbly', 'courteous', 'diligent', 'exuberant', 'fabulous', 'genuine', 'hilarious', 'impressive', 'jollyhearted',
        'knowledgeable', 'likable', 'majestic', 'noble', 'outstanding', 'proactive', 'quickwitted', 'resolute',
        'skilled', 'talented', 'uplifting', 'versatile', 'winsome', 'youthful', 'zenlike', 'admirable', 'boldhearted',
        'charismatic', 'dynamic', 'excellent', 'fascinating', 'gleeful', 'honesthearted', 'ingenious', 'jubilanthearted',
        'keenhearted', 'loving', 'magnificent', 'noblebrave', 'outgoinghearted', 'positive', 'reliable', 'sensitive',
        'trustworthyhearted', 'vibranthearted', 'wisebrave', 'zealoushearted', 'adventuroushearted', 'breezy',
        'courageous', 'devotedhearted', 'exemplary', 'flamboyant', 'gracioushearted', 'heroic', 'inspired', 'joyous',
        'kindspirited', 'luminescent', 'motivational', 'neatspirited', 'optimistichearted', 'proficient', 'resolutehearted',
        'sagacioushearted', 'tenacioushearted', 'uniquehearted', 'valianthearted', 'wittyhearted', 'zealousspirited',
        'boldbrave', 'chipper', 'daringhearted', 'effervescent', 'fearlesshearted', 'gleaming', 'humbledhearted', 'jubilated',
        'kindheartedbrave', 'luminoushearted', 'meticulous', 'notable', 'outshining', 'productive', 'resilienthearted',
        'skillful', 'talentedhearted', 'unstoppable', 'vigilanthearted', 'zanyspirited', 'agilehearted', 'boisterous',
        'charitable', 'determinedhearted', 'exuberanthearted', 'fiercehearted', 'gracefulhearted', 'humorous', 'jubilantspirited',
        'kindbrave', 'livelyhearted', 'mirthful', 'nurturinghearted', 'observanthearted', 'peaceful', 'radianthearted',
        'spiritedhearted', 'trustworthyspirited', 'uplifted', 'vivacious', 'warmhearted', 'zealousbrave', 'ambitious',
        'brighthearted', 'considerate', 'dedicatedhearted', 'excellentspirited', 'fearsomehearted', 'glowing', 'heartfelt',
        'ingenioushearted', 'joyfulhearted', 'kindheartedspirited', 'loyalhearted', 'mindful', 'nobleheartedspirited',
        'optimisticspirited', 'proactivehearted', 'quickspirited', 'resolutespirited', 'sagaciousspirited', 'steadfasthearted',
        'trustworthyheartedspirited', 'valoroushearted', 'wittyspirited', 'zestfulhearted', 'adventurousspirited', 'boldheartedspirited',
        'charminghearted', 'dynamichearted', 'effortless', 'flourishinghearted', 'genuinehearted', 'humblehearted', 'imaginativespirited',
        'jovialhearted', 'kindlyhearted', 'lighthearted', 'motivatedhearted', 'neathearted', 'outgoingheartedspirited',
        'peacefulhearted', 'resilientspirited', 'skilledhearted', 'talentedspirited', 'upbeatspirited', 'versatilehearted',
        'welcominghearted', 'zenhearted'
    ];

    private const ANIMALS = [
        'aardvark', 'albatross', 'alligator', 'ant', 'anteater', 'antelope', 'ape', 'armadillo', 'baboon', 'badger',
        'bat', 'bear', 'beaver', 'bee', 'bison', 'boar', 'buffalo', 'butterfly', 'camel', 'capybara', 'caribou', 'cat',
        'caterpillar', 'chameleon', 'cheetah', 'chimpanzee', 'chinchilla', 'cobra', 'cockroach', 'cougar', 'cow',
        'coyote', 'crab', 'crane', 'crocodile', 'crow', 'deer', 'dingo', 'dodo', 'dog', 'dolphin', 'donkey', 'dove',
        'dragonfly', 'duck', 'eagle', 'eel', 'elephant', 'emu', 'falcon', 'ferret', 'finch', 'firefly', 'fish', 'flamingo',
        'fly', 'fox', 'frog', 'gazelle', 'gecko', 'giraffe', 'gnu', 'goat', 'goldfish', 'goose', 'gorilla', 'grasshopper',
        'grouse', 'guinea_pig', 'gull', 'hamster', 'hare', 'hawk', 'hedgehog', 'heron', 'hippopotamus', 'hornet',
        'horse', 'hummingbird', 'hyena', 'ibis', 'iguana', 'jackal', 'jaguar', 'jellyfish', 'kangaroo', 'kingfisher',
        'koala', 'komodo_dragon', 'kudu', 'ladybug', 'lemur', 'leopard', 'lion', 'lizard', 'llama', 'lobster', 'lynx',
        'macaque', 'magpie', 'manatee', 'mandrill', 'marmoset', 'meerkat', 'mink', 'mole', 'mongoose', 'monkey',
        'moose', 'mosquito', 'mouse', 'mule', 'narwhal', 'newt', 'nightingale', 'ocelot', 'octopus', 'okapi', 'opossum',
        'ostrich', 'otter', 'owl', 'ox', 'oyster', 'panda', 'panther', 'parrot', 'peacock', 'pelican', 'penguin',
        'pheasant', 'pig', 'pigeon', 'platypus', 'polar_bear', 'pony', 'porcupine', 'porpoise', 'possum', 'prairie_dog',
        'prawn', 'puffin', 'quail', 'rabbit', 'raccoon', 'ram', 'rat', 'raven', 'red_panda', 'reindeer', 'rhinoceros',
        'rooster', 'salamander', 'salmon', 'sandpiper', 'sardine', 'scorpion', 'seahorse', 'seal', 'shark', 'sheep',
        'shrew', 'shrimp', 'skunk', 'sloth', 'snail', 'snake', 'sparrow', 'spider', 'squid', 'squirrel', 'starfish',
        'stork', 'swallow', 'swan', 'tapir', 'tarsier', 'termite', 'tiger', 'toad', 'turkey', 'turtle', 'viper',
        'vulture', 'wallaby', 'walrus', 'warthog', 'wasp', 'weasel', 'whale', 'wildcat', 'wolf', 'wombat', 'woodpecker',
        'worm', 'wren', 'yak', 'zebra', 'zorilla', 'axolotl', 'bandicoot', 'barracuda', 'basilisk', 'beetle', 'bird',
        'blobfish', 'boa', 'booby', 'bushbaby', 'cassowary', 'catfish', 'coati', 'coyote', 'cuscus', 'dassie', 'dhole',
        'dikdik', 'dugong', 'echidna', 'eland', 'eland', 'emu', 'fossa', 'frogfish', 'galago', 'gerbil', 'giant_panda',
        'gibbon', 'giraffe', 'goral', 'guanaco', 'guenon', 'hagfish', 'heron', 'hog', 'hornbill', 'horned_lizard', 'ibex',
        'indri', 'jackrabbit', 'kinkajou', 'kipunji', 'kob', 'kookaburra', 'krill', 'kudu', 'lapwing', 'lemur', 'lionfish',
        'loris', 'lungfish', 'macaque', 'macaw', 'malayan_tiger', 'manatee', 'mara', 'margay', 'marmoset', 'marten',
        'meerkat', 'mongoose', 'muskox', 'naked_molerat', 'numbat', 'nyala', 'okapi', 'paca', 'pangolin', 'peccary',
        'potoroo', 'proboscis', 'quokka', 'raccoondog', 'rattlesnake', 'saiga', 'saola', 'serval', 'shoebill', 'shrew',
        'sifaka', 'snub_nosed', 'solenodon', 'spiny_anteater', 'takin', 'tamandua', 'tamarin', 'tapir', 'tasmanian_devil',
        'tenrec', 'thrush', 'tortoise', 'toucan', 'tucuxi', 'uakari', 'vicuna', 'vulture', 'wallaroo', 'waterbuck',
        'wild_dog', 'wildebeest', 'yellow_mongoose', 'zorilla'
    ];

    public static function generate(bool $plular = false): string
    {
        $randomAdjective = self::ADJECTIVES[array_rand(self::ADJECTIVES)];
        $randomAnimal = self::ANIMALS[array_rand(self::ANIMALS)];
        
        $reference = $randomAdjective . '_' . $randomAnimal;

        if ($plular) {
            if (in_array(substr($randomAnimal, -1), ['e', 'o', 'x', 's', 'z'])) {
                $reference .= 's';
            } else {
                $reference .= 'es';
            }
        }

        return $reference;
    }
}