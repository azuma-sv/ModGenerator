Filter rules:

Item - Entity name. It's a string which always starts from big letter.

^ - Special char which matches any entity name.
^Package - Equivalent of: .*Package
Package^ - Equivalent of: Package.*

@ - Attributes separator. Always attached to single entity name if it has conditions dependent on attributes.
Item@sonarsize - Check if Item objec has attribute sonarsize. This rule will select all items which are visible on sonar.

= - Equality check. Compare if attribute value is equal with provided.
ContentPackage@name=Vanilla - Proper way to use equality check.

! - Inequality check. Determines if attribute value is NOT equal with provided.
Item@tag!smallitem - Proper way to use inequality check.


ContentPackage@name=Vanilla?Item@name=SomeItem - Proper way to use Attribute separator.
ContentPackage?Item@name=SomeItem - This will select all content packages or Items, but search for name tag will get applied only to Item objects.
