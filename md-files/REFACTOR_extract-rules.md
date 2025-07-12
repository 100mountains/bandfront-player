rules to list relevant code from an OLD CODEBASE in terms of a new one.

** OLD CODEBASE RULES **

we want to extract, explain and document 4 things from this old codebase:

1. - the player - it was context aware - main shop page / product page. allowed single player or selection of tracks on full playlist, selection of track hightlighted it, and pressing the track played it. on the front page it had logic to start a renderer on the cover of the product etc

2. - widget rendering ! 

3. - block rendered a playlist as soon as you selected it! 

4. code that is relevant to handling the logic of the above 3 rules. or if its dealing with anything in MAP_STATE_CONFIG_&_VARIABLES.md related to the above 3 rules. beware names may have changed slightly in the variables, some have bfp_ removed, some correlate to wcmp_ at the start. the intent is the same as the state management variables from this document show you what we are looking for. 


** NEW CODEBASE **

- any logic, icons, pngs has to be transferred into the new system laid out in AI-CODE-RULES
- we want it to work like it BUT within our new system 
- new version supports mostly default css players
- may support pngs later
- has removed many over-rides and now uses state-manager.php
- split up concerns into class based system

** EXTRACTION RULES **
to extract a piece of code into our output, we must be sure:
1. it is relevant to points 1,2,3 and 4 of the OLD CODEBASE RULES
2. condense logic only parts, do not duplicate.
2. each extraction must clearly show the variables it needs to be set to run, with our new naming system, show in the MAP_STATE_CONFIG_&_VARIABLES.md file.
2. if relevant. it can show in what context its being called e.g. as a widget, block, etc 
3. it needs to show inputs and outputs, type of function and any PHP or javascript information needed for a programmer. 

