<div id="settingsContainer">
    <div id="settingsContentTop">
        <div class="iconContainer">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                <path d="M0 96C0 78.3 14.3 64 32 64l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 128C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32zM448 416c0 17.7-14.3 32-32 32L32 448c-17.7 0-32-14.3-32-32s14.3-32 32-32l384 0c17.7 0 32 14.3 32 32z"/>
            </svg>
        </div>
        <h2>Navigation</h2>
    </div>
    <div id="chooseNavigation">
        <div class="inputContainer">
            <label>Choose Navigation</label>
            <select id="navigationSelect" class="input">
                <option value="-1">---</option>
                <?php foreach($data['navigations'] as $navigation): ?>
                    <option value="<?php echo $navigation->getId(); ?>"><?php echo $navigation->getLabel(); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button id="createNewNavigation" class="btn noMargin hollow glow smallFont">Create New</button>
        <button id="deleteNavigation" class="btn noMargin error hollow smallFont">Delete</button>
    </div>

    <div id="navigationContent">

    </div>
</div>