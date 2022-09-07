IsActive - Attribute name. It's a string which contains letters matching this regular expression: "^[a-zA-Z\d\-_]*$".
This string is not a filter rule. It will ask mod generator to set a value for this attribute.

Filter rules:

| - Attributes separator. Always attached to end of entity name (or to beginning of attributes section).
Items - Will select all entities of type "Items".
sonarsize - Will select all entities which have an attribute "sonarsize".
Items|sonarsize - Will do both at the same time. Will select all entities of type "Items" which are visible on sonar.

LightComponent@ - Entity name. It's a string which contains only letters matching this regular expression: "^[a-zA-Z\d\-_]*@".
But it always need to be followed with "@" letter. Otherwise, it will be used as attribute.

= - Equality check. Compare if attribute value is equal with provided.
ContentPackage@name=Vanilla - It will select Barotrauma core content package entity.

! - Inequality check. Determines if attribute value is NOT equal with provided.
Item@tag!smallitem - Will select every "Item" entity which is not a "smallitem".

* - Contains check. Determines if attribute value contains specific piece of value.
Item@identifier*fabricator - Will select every "Item" entity with "fabricator" as part of its identifier attribute.

, - Array separator. Converts string to an array.
Item@tag=smallitem,ore - This will check if "Item" entity is a "smallitem" AND an "ore" at the same time.

[] - Array wrapper. Also converts string to an array, but uses OR operator for comparison.
Item@tag=[ore,plant] - This will check if "Item" entity has at least one of the tags "ore" OR "plant".

+ - AND condition operator. Can stack multiple conditions (AND operator has higher priority than OR).
Item@tag=smallitem+impactsoundtag=impact_metal_light - Will select every "Item" entity which is a "smallitem" AND has metal impact sound.

? - OR condition operator. Can stack multiple conditions (OR operator has lower priority than AND).
Item@tag=smallitem+impactsoundtag=impact_metal_light?tag=smallitem+impactsoundtag=impact_soft - Will select every "Item" entity which
is a "smallitem" and has metal impact sound OR will select every "Item" which is a "smallitem" and has a soft impact sound.
Item@tag=smallitem+impactsoundtag=[impact_metal_light,impact_soft] - The same example as above, but has more simple syntax.

/ - Sub-element operator. Applies query rules to entity child elements.
Item@tag=ore/Price/Price@storeidentifier=merchantmine - This will select all "Price" sub-elements of every "ore" entity which can be bought at mining station.

< - Internal condition operator. Similar to sub-element operator "/", but it impacts resulting context.
Item@tag=ore<Price/Price@storeidentifier=merchantmine - This will select all "Item" entities if their tag is "ore" and if it can be bought on mining station.
Item@tag=ore<Price<Price@storeidentifier=merchantmine - WRONG example of usage of this operator.
You can use this operator only once per query. If you have multiple - only the last one will take effect. All previous "<" letters will be just ignored and used as "/" sign.
ps. Query can't return a context with multiple different types of objects. This will make mod generation very chaotic and unpredictable.

\ - Protection operator. Protect letters from being parsed by a query parser.
Item@tag=ore/DecorativeSprite@texture=Content\/Items\/Materials\/MineralEnvironment.png - Will select all "DecorativeSprite" sub-elements which
have a texture "Content/Items/Materials/MineralEnvironment.png" and only if they belong to "Item" entity which is an "ore".

{} - Order operator. Selects an element by its order number. Can be useful for tags which have no unique ID.
Item@tag=ore/DecorativeSprite{3} - Will select every third "DecorativeSprite" sub-element of every "Item" entity which is an "ore".
Item@tag=ore/DecorativeSprite@texture=Content\/Items\/Materials\/MineralEnvironment.png{3} - Will select every third "DecorativeSprite"
sub-element from A SET OF decorative sprites which have mentioned texture file. IT WILL IGNORE order number of sprites which have another texture.
Item@tag=ore/DecorativeSprite{3}@texture=Content\/Items\/Materials\/MineralEnvironment.png - This is an example of WRONG usage of this operator.
It will work only if it goes directly after the attributes.
Item@tag=ore/DecorativeSprite{3}/AnimationConditional@isactive - This is a CORRECT example of usage of order operator.
Item@tag=ore/DecorativeSprite{-1} - It can use negate values. This query will pick the last decorative sprite.

"" - Strict value operator. Helps to make strict comparisons and ignore parser syntax inside them.
Item@tag=ore/DecorativeSprite@randomrotation="-20,20" - This will not break "-20,20" into array like [-20,20]. This will strictly compare the value
from filter rule to value inside the attribute.
Item@tag="ore,smallitem" - This check will fail for all items which have a tag value "smallitem,ore". You should keep this in mind.
Item@tag=ore/DecorativeSprite@randomrotation=-20\,20 - Another CORRECT example.
Item@tag=ore/DecorativeSprite@texture="Content/Items/Materials/MineralEnvironment.png" - One more CORRECT example. Helps to serve paths to files.
Item@name="The \"YAHOO!\" item" - And another one CORRECT example.
Item@name="The "YAHOO!" item" - WRONG example. Parser will be unable to parse that. ????
Item@name="Welder \ Cutter" - This will work as expected. Command will be parsed as is: .
Item@name="\" - This will NOT work as expected. This will break parser syntax.
Item@name="\\" - This WILL work as expected. You will see single protection char \
Item@name="\"" - This WILL work as expected. You will see single bracket char "


% - Variable operator. Will replace string with a variable content. Works only if matches with this regular expression: "%[\dA-Z_>]*".
%VAR_123 - Will get replaced with a value.
$ARRAY>KEY_1 - Can work with arrays.
Item@name="The %ITEM_NAME item" - Can work inside the strict comparison operator even if it contains parser syntax like: "".

Can work with variables inside the variables:
ARRAY:
  KEY: The "YAHOO!" item
PATH_TO_ANOTHER_VAR: '%ARRAY>KEY'
execute:
 Item@name=%%PATH_TO_ANOTHER_VAR

This example WORKS nice:
ITEM_NAME: '"YAHOO!"'
ARRAY:
  KEY: The %ITEM_NAME item
PATH_TO_ANOTHER_VAR: '%ARRAY>KEY'
execute:
 Item@name=%%PATH_TO_ANOTHER_VAR
As result you will get something like:
execute:
 Item@name=The \"YAHOO\!\" item

This example WORKS nice:
ITEM:
  TAGS:
    - ore
    - plant
execute:
 Item@tag=[%ITEM>TAGS]
As result you will get something like:
execute:
 Item@tag=[ore,plant]
It's a good habit to keep all changes you apply inside variables. Helps to modify mod easier by just changing variables.

Barotrauma constant %ModDir% is always ignored and can't be used as a variable by mod generator.


Super complex selector:
Item@name="%%PATH_TO_ANOTHER_VAR>%KEY_NAME"?tag=[ore,plant]<DecorativeSprite@texture=Content\/Items\/Materials\/MineralEnvironment.png+randomrotation="-20,20"{3}