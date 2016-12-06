import pandas as pd
import numpy as np
import csv
import datetime
import re

pd.set_option('display.max_row',10)
pd.set_option('display.max_colwidth',225)
def find_meters(input_file):
    trendmeters = []
    df = pd.read_csv(input_file,
                     #nrows=400000,
                     converters={'Object Value': drop_units})
    df.sort_values(by=['Object Name', 'Date / Time'], inplace=True)
    for trend in (df['Object Name'].unique()):
        z = (df[df['Object Name'] == trend]['Object Value'])
        summary = check_summarizable(np.array(z))
        trendmeters.append((trend,summary))
    return trendmeters



def check_summarizable(series):
    boolseriesrising = ((series[1:] - series[:-1]) > 0)
    boolseriesbroken = ((series[1:] - series[:-1]) == 0)
    boolseriesfalling = ((series[1:] - series[:-1]) < 0)
    rising = boolseriesrising.mean()
    broken = boolseriesbroken.mean()
    falling = boolseriesfalling.mean()
    #print('broken', broken)
    #print('rising', rising)
    #print('falling', falling)
    #print('total', broken + rising + falling)
    if broken > .9:
        return False
    elif rising / (rising + falling) > .9:
        return True
    else:
        return False


def drop_units(value):
    """Remove the units from a string, e.g. '309.2 kWh' -> 309.2"""
    pattern = re.compile(r"\A(\d*\.?\d+) ?[a-zA-Z]*\Z")
    match = pattern.match(value)
    if match is None:
        # value was not of the expected format
        return np.nan
    else:
        return float(match.group(1))

def only_meters(array):
    with open('meters.txt', 'w') as f:
        for entry in array:
            if entry[1]:
                print(entry[0])
                f.write(str(entry[0]) + '\n')


print(datetime.datetime.now())
q = find_meters('input/2016 08 21 - AHS-Electric  & GasTrend-July 2015  - Aug 2016.csv')
only_meters(q)
print(datetime.datetime.now())