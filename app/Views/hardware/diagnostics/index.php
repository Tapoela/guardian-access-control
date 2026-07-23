<?= $this->extend('layout') ?>

<?= $this->section('content') ?>

<div class="container-fluid">

    <div class="card">

        <div class="card-header">
            <h4>
                <i class="bi bi-cpu"></i>
                Hardware Diagnostics
            </h4>
        </div>


        <div class="card-body">


            <div class="row">


                <div class="col-md-6">


                    <table class="table table-bordered">

                        <tr>
                            <th>Site</th>
                            <td>
                                <?= $device['FkSiteId'] ?>
                            </td>
                        </tr>


                        <tr>
                            <th>Device</th>
                            <td>
                                <?= $device['DeviceName'] ?>
                            </td>
                        </tr>


                        <tr>
                            <th>IP Address</th>
                            <td>
                                <?= $device['IPAddress'] ?>
                            </td>
                        </tr>


                        <tr>
                            <th>TCP Port</th>
                            <td>
                                <?= $device['TcpPort'] ?>
                            </td>
                        </tr>


                    </table>


                </div>


                <div class="col-md-6">


                    <div class="alert alert-secondary">

                        Connection Status:

                        <span id="connectionStatus">
                            Checking...
                        </span>


                    </div>


                </div>


            </div>



            <hr>


            <div class="card mt-3">

                <div class="card-header">
                    Raw DCON Command
                </div>

                <div class="card-body">

                    <div class="input-group mb-3">

                        <input
                            type="text"
                            id="command"
                            class="form-control"
                            value="$01M">


                        <button
                            class="btn btn-primary"
                            onclick="sendCommand()">

                            Send

                        </button>

                    </div>

                    <small class="text-muted">
                        Examples:
                        $01M (module info)<br>
                        #011001 (RL1 ON)<br>
                        #011000 (RL1 OFF)
                    </small>

                </div>

                <h5>
                Response
            </h5>


            <pre
                class="bg-dark text-white p-3"
                id="response"
                style="min-height:150px;"></pre>

            </div>


            <div class="card mt-3">

                <div class="card-header">
                    Relay Test
                </div>

                <div class="card-body">

                    <!-- Changed from 1 to 0 (Targets Physical Relay 1) -->
                    <button 
                    class="btn btn-warning"
                    onclick="setRelay(0,1)">
                        Boom Relay ON
                    </button>

                    <button 
                    class="btn btn-secondary"
                    onclick="setRelay(0,0)">
                        Boom Relay OFF
                    </button>

                    <!-- Changed from 2 to 1 (Targets Physical Relay 2) -->
                    <button 
                    class="btn btn-success"
                    onclick="setRelay(1,1)">
                        Green ON
                    </button>

                    <button 
                    class="btn btn-dark"
                    onclick="setRelay(1,0)">
                        Green OFF
                    </button>

                    <!-- Changed from 3 to 2 (Targets Physical Relay 3) -->
                    <button 
                    class="btn btn-danger"
                    onclick="setRelay(2,1)">
                        Red ON
                    </button>

                    <button 
                    class="btn btn-secondary"
                    onclick="setRelay(2,0)">
                        Red OFF
                    </button>

                    <button 
                        class="btn btn-primary"
                        onclick="readRelayStatus()">
                        Read Relay Status
                    </button>

                </div>

            </div>

            </div>

            <br>

            <div class="card mt-3">

                <div class="card-header">
                    Serial Configuration
                </div>

                <div class="card-body">

                    <table class="table table-bordered">

                        <tr>
                            <th>7188 Baud</th>
                            <td id="baudDisplay">
                                115200
                            </td>
                        </tr>

                        <tr>
                            <th>Protocol</th>
                            <td>
                                DCON
                            </td>
                        </tr>

                        <tr>
                            <th>RS485 Status</th>
                            <td id="rsStatus">
                                Unknown
                            </td>
                        </tr>

                    </table>

                </div>

            </div>

        </div>

    </div>


</div>


<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script>

const deviceId = <?= $device['Id']; ?>;

function sendCommand()
{

    let command =
        document.getElementById('command').value;


    fetch("<?= base_url('hardware/sendRaw') ?>",
    {

        method:"POST",

        headers:
        {
            "Content-Type":
            "application/x-www-form-urlencoded"
        },

        body:
        "deviceId=" + encodeURIComponent(deviceId) +
        "&command=" + encodeURIComponent(command)

    })

    .then(response => response.json())

    .then(data =>
    {

        document.getElementById('response').innerHTML =
            JSON.stringify(data,null,4);

    });

}

function scanModules()
{
    document.getElementById('response').innerHTML = "Scanning...";

    fetch("<?= base_url('hardware/scanModules') ?>", {
        method: "POST"
    })

    .then(response => response.json())

    .then(data => {

        let output = "";

        data.results.forEach(item => {

            output +=
                "Address: " + item.address +
                "\nTX: " + item.tx +
                "\nRX: " + item.rx +
                "\n---------------------\n";

        });


        document.getElementById('response').innerHTML = output;

    })

    .catch(error => {

        document.getElementById('response').innerHTML =
            "Error: " + error;

    });
}

function scanBaud()
{

    document.getElementById('response').innerHTML =
        "Scanning baud rates...";


    fetch("<?= base_url('hardware/readConfig') ?>",{

        method:"POST",

        headers:{
            "Content-Type":"application/x-www-form-urlencoded"
        },

        body:
        "deviceId=" + encodeURIComponent(deviceId)

    })

    .then(response=>response.json())

    .then(data=>{


        let output="";


        data.results.forEach(item=>{


            output +=

            "Baud: " + item.baud +
            "\nTX: " + item.tx +
            "\nRX: " + item.rx +
            "\n";


            if(item.found)
            {
                output += ">>> RESPONSE FOUND <<<\n";
            }


            output +=
            "----------------------\n";


        });



        document.getElementById('response').innerHTML =
            output;


    });

}

function readConfig()
{

fetch("<?= base_url('hardware/readConfig') ?>",{

    method:"POST",

    headers:{
        "Content-Type":"application/x-www-form-urlencoded"
    },

    body:
    "deviceId=" + encodeURIComponent(deviceId)

})

.then(response=>response.json())

.then(data=>{

document.getElementById('response').innerHTML =
JSON.stringify(data,null,4);

});

}

function testRelay()
{

fetch("<?= base_url('hardware/readConfig') ?>",{

    method:"POST",

    headers:{
        "Content-Type":"application/x-www-form-urlencoded"
    },

    body:
    "deviceId=" + encodeURIComponent(deviceId)

})

.then(response=>response.json())

.then(data=>{

document.getElementById('response').innerHTML =
JSON.stringify(data,null,4);

});

}

function setRelay(relay, state)
{
    fetch("<?= base_url('hardware/setRelay') ?>", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body:

        "deviceId=" + encodeURIComponent(deviceId) +

        "&relay=" + encodeURIComponent(relay) +

        "&state=" + encodeURIComponent(state)
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('response').innerHTML = JSON.stringify(data, null, 4);
    })
    .catch(error => {
        document.getElementById('response').innerHTML = "Error: " + error;
    });
}

function readRelayStatus()
{
    fetch("<?= base_url('hardware/readRelayStatus') ?>",{

        method:"POST",

        headers:{
        "Content-Type":"application/x-www-form-urlencoded"
        },

        body:
        "deviceId=" + encodeURIComponent(deviceId)

    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('response').innerHTML = JSON.stringify(data, null, 4);
    });
}

checkConnection();

function checkConnection()
{
    fetch("<?= base_url('hardware/connectionStatus') ?>",{

        method:"POST",

        headers:{
            "Content-Type":"application/x-www-form-urlencoded"
        },

        body:
        "deviceId=" + encodeURIComponent(deviceId)

    })

    .then(r=>r.json())

    .then(data=>{

        if(data.success)
        {
            document.getElementById("connectionStatus").innerHTML =
                "🟢 Online (" + data.time + " ms)";
        }
        else
        {
            document.getElementById("connectionStatus").innerHTML =
                "🔴 Offline";
        }

    });
}

</script>


<?= $this->endSection() ?>